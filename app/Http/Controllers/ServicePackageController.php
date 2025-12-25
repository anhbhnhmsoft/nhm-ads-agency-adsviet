<?php

namespace App\Http\Controllers;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServicePackage\ServicePackageFeature;
use App\Common\Helpers\TimezoneHelper;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Http\Resources\ServicePackageListResource;
use App\Http\Resources\ServicePackageResource;
use App\Service\ServicePackageService;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServicePackageController extends Controller
{

    public function __construct(
        protected ServicePackageService $servicePackageService
    )
    {
    }

    /**
     * Hiển thị danh sách gói dịch vụ
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request): \Inertia\Response
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->servicePackageService->getListServicePackage(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));
        return $this->rendering(
            view: 'service-package/index',
            data: [
                'paginator' => fn () => ServicePackageListResource::collection($result->getData()),
            ]
        );
    }

    /**
     * Hiển thị form tạo gói dịch vụ
     * @return \Inertia\Response
     */
    public function createView(): \Inertia\Response
    {
        return $this->rendering(
            view: 'service-package/create',
            data: [
                'meta_features' => ServicePackageFeature::getOptionsByPlatform('meta'),
                'google_features' => ServicePackageFeature::getOptionsByPlatform('google'),
                'meta_timezones' => TimezoneHelper::getMetaTimezoneOptions(),
                'google_timezones' => TimezoneHelper::getGoogleTimezoneOptions(),
            ]
        );
    }

    /**
     * Xử lý tạo gói dịch vụ
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(),
            [
            'name' => ['required', 'string', 'max:255'],
            // description: cho phép độ dài lớn, không giới hạn ở 255 ký tự nữa
            'description' => ['required', 'string'],
            'platform' => ['required', Rule::in(PlatformType::getValues())],
            'features' => ['required', 'array'],
            'monthly_spending_fee_structure' => ['nullable', 'array'],
            'monthly_spending_fee_structure.*.range' => ['required_with:monthly_spending_fee_structure', 'string', 'max:255'],
            'monthly_spending_fee_structure.*.fee_percent' => ['required_with:monthly_spending_fee_structure', 'string', 'max:50'],
            'open_fee' => ['required', 'numeric', 'min:0'],
            'range_min_top_up' => ['required', 'numeric', 'min:0'],
            'top_up_fee' => ['required', 'numeric', 'min:0'],
            'set_up_time' => ['required', 'numeric', 'min:0'],
            'disabled' => ['required', 'boolean'],
        ],
            [
            'name.required' => __('services.validation.name_invalid'),
            'name.string' => __('services.validation.name_invalid'),
            'description.required' => __('services.validation.description_invalid'),
            'description.string' => __('services.validation.description_invalid'),
            'platform.required' => __('services.validation.platform_invalid'),
            'platform.in' => __('services.validation.platform_invalid'),
            'features.required' => __('services.validation.features_invalid'),
            'monthly_spending_fee_structure.array' => __('services.validation.monthly_spending_fee_invalid'),
            'monthly_spending_fee_structure.*.range.required_with' => __('services.validation.monthly_spending_fee_invalid'),
            'monthly_spending_fee_structure.*.range.string' => __('services.validation.monthly_spending_fee_invalid'),
            'monthly_spending_fee_structure.*.fee_percent.required_with' => __('services.validation.monthly_spending_fee_invalid'),
            'monthly_spending_fee_structure.*.fee_percent.string' => __('services.validation.monthly_spending_fee_invalid'),
            'open_fee.required' => __('services.validation.open_fee_invalid'),
            'range_min_top_up.required' => __('services.validation.range_min_top_up_invalid'),
            'top_up_fee.required' => __('services.validation.top_up_fee_invalid'),
            'set_up_time.required' => __('services.validation.set_up_time_invalid'),
            'disabled.required' => __('services.validation.disabled_invalid'),
            'disabled.boolean' => __('services.validation.disabled_invalid'),
        ]
        );
        //logic validate features
        $validator->after(function ($validator) {
            // Lấy dữ liệu đang được validate
            $data = $validator->getData();
            $platformValue = $data['platform'] ?? null;
            $features = $data['features'] ?? [];

            // Nếu platform không có, rule 'required' ở trên đã bắt lỗi, ta không cần làm gì
            if (empty($platformValue)) {
                return;
            }

            // Lấy Enum platform
            $platformEnum = PlatformType::tryFrom((int)$platformValue);
            if (!$platformEnum) {
                return; // Bỏ qua nếu platform không hợp lệ (đã bị bắt bởi Rule::in)
            }
            
            if ($platformEnum === PlatformType::META || $platformEnum === PlatformType::GOOGLE) {
                foreach ($features as $index => $feature) {
                    if (!isset($feature['key']) || !isset($feature['value'])) {
                        $validator->errors()->add(
                            'features',
                            __('services.validation.features_invalid')
                        );
                        return;
                    }
                }
                return;
            }
        });

        // Validate dữ liệu
        $validator->validate();

        // Lấy dữ liệu đã validate
        $form = $validator->validated();
        $form = $this->prepareMonthlySpendingData($form);

        // Tạo service package
        $result = $this->servicePackageService->createServicePackage($form);

        // Xử lý kết quả
        if($result->isSuccess()){
            FlashMessage::success(__('common_success.add_success'));
            return redirect()->route('service_packages_index');
        }else{
            FlashMessage::error($result->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Hiển thị form chỉnh sửa gói dịch vụ
     * @param string $id
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function editView(string $id): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        $result = $this->servicePackageService->getServicePackageById($id);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->route('service_packages_index');
        }
        return $this->rendering(
            view: 'service-package/edit',
            data: [
                'meta_features' => ServicePackageFeature::getOptionsByPlatform('meta'),
                'google_features' => ServicePackageFeature::getOptionsByPlatform('google'),
                'meta_timezones' => TimezoneHelper::getMetaTimezoneOptions(),
                'google_timezones' => TimezoneHelper::getGoogleTimezoneOptions(),
                'service_package' => fn () => ServicePackageResource::make($result->getData())->toArray(request()),
            ]
        );
    }

    /**
     * Xử lý cập nhật gói dịch vụ
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function update(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(),
            [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'platform' => ['required', Rule::in(PlatformType::getValues())],
                'features' => ['required', 'array'],
                'monthly_spending_fee_structure' => ['nullable', 'array'],
                'monthly_spending_fee_structure.*.range' => ['required_with:monthly_spending_fee_structure', 'string', 'max:255'],
                'monthly_spending_fee_structure.*.fee_percent' => ['required_with:monthly_spending_fee_structure', 'string', 'max:50'],
                'open_fee' => ['required', 'numeric', 'min:0'],
                'range_min_top_up' => ['required', 'numeric', 'min:0'],
                'top_up_fee' => ['required', 'numeric', 'min:0'],
                'set_up_time' => ['required', 'numeric', 'min:0'],
                'disabled' => ['required', 'boolean'],
            ],
            [
                'name.required' => __('services.validation.name_invalid'),
                'name.string' => __('services.validation.name_invalid'),
                'description.required' => __('services.validation.description_invalid'),
                'description.string' => __('services.validation.description_invalid'),
                'platform.required' => __('services.validation.platform_invalid'),
                'platform.in' => __('services.validation.platform_invalid'),
                'features.required' => __('services.validation.features_invalid'),
                'monthly_spending_fee_structure.array' => __('services.validation.monthly_spending_fee_invalid'),
                'monthly_spending_fee_structure.*.range.required_with' => __('services.validation.monthly_spending_fee_invalid'),
                'monthly_spending_fee_structure.*.range.string' => __('services.validation.monthly_spending_fee_invalid'),
                'monthly_spending_fee_structure.*.fee_percent.required_with' => __('services.validation.monthly_spending_fee_invalid'),
                'monthly_spending_fee_structure.*.fee_percent.string' => __('services.validation.monthly_spending_fee_invalid'),
                'open_fee.required' => __('services.validation.open_fee_invalid'),
                'open_fee.numeric' => __('services.validation.open_fee_invalid'),
                'range_min_top_up.required' => __('services.validation.range_min_top_up_invalid'),
                'range_min_top_up.numeric' => __('services.validation.range_min_top_up_invalid'),
                'top_up_fee.required' => __('services.validation.top_up_fee_invalid'),
                'top_up_fee.numeric' => __('services.validation.top_up_fee_invalid'),
                'set_up_time.required' => __('services.validation.set_up_time_invalid'),
                'set_up_time.numeric' => __('services.validation.set_up_time_invalid'),
                'disabled.required' => __('services.validation.disabled_invalid'),
                'disabled.boolean' => __('services.validation.disabled_invalid'),
            ]
        );
        //logic validate features
        $validator->after(function ($validator) {
            // Lấy dữ liệu đang được validate
            $data = $validator->getData();
            $platformValue = $data['platform'] ?? null;
            $features = $data['features'] ?? [];

            // Nếu platform không có, rule 'required' ở trên đã bắt lỗi, ta không cần làm gì
            if (empty($platformValue)) {
                return;
            }

            // Lấy Enum platform
            $platformEnum = PlatformType::tryFrom((int)$platformValue);
            if (!$platformEnum) {
                return;
            }
            
            if ($platformEnum === PlatformType::META || $platformEnum === PlatformType::GOOGLE) {
                foreach ($features as $index => $feature) {
                    if (!isset($feature['key']) || !isset($feature['value'])) {
                        $validator->errors()->add(
                            'features',
                            __('services.validation.features_invalid')
                        );
                        return;
                    }
                }
                return;
            }
        });

        // Validate dữ liệu
        $validator->validate();

        // Lấy dữ liệu đã validate
        $form = $validator->validated();
        $form = $this->prepareMonthlySpendingData($form);

        // Cập nhật service package
        $result = $this->servicePackageService->updateServicePackage($id, $form);

        // Xử lý kết quả
        if($result->isSuccess()){
            FlashMessage::success(__('common_success.update_success'));
            return redirect()->route('service_packages_index');
        }else{
            FlashMessage::error($result->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Xử lý xóa gói dịch vụ
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id): \Illuminate\Http\RedirectResponse
    {
        $result = $this->servicePackageService->deleteServicePackage($id);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.delete_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->route('service_packages_index');
    }

    /**
     * Chuyển trạng thái kích hoạt/ vô hiệu gói dịch vụ
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleDisable(string $id): \Illuminate\Http\RedirectResponse
    {
        $result = $this->servicePackageService->toggleDisable($id);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.toggle_disable_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->route('service_packages_index');
    }

    /**
     * Chuẩn hóa dữ liệu cấu trúc phí theo mức chi tiêu hằng tháng
     */
    private function prepareMonthlySpendingData(array $form): array
    {
        $form['monthly_spending_fee_structure'] = collect($form['monthly_spending_fee_structure'] ?? [])
            ->map(function ($row) {
                return [
                    'range' => trim((string) ($row['range'] ?? '')),
                    'fee_percent' => trim((string) ($row['fee_percent'] ?? '')),
                ];
            })
            ->filter(function ($row) {
                return $row['range'] !== '' || $row['fee_percent'] !== '';
            })
            ->values()
            ->all();

        return $form;
    }
}
