<?php

namespace App\Http\Controllers;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServicePackage\ServicePackageFeature;
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

    public function createView(): \Inertia\Response
    {

        return $this->rendering(
            view: 'service-package/create',
            data: [
                'meta_features' => ServicePackageFeature::getOptionsByPlatform('meta'),
                'google_features' => ServicePackageFeature::getOptionsByPlatform('google'),
            ]
        );
    }

    /**
     * @throws ValidationException
     */
    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(),
            [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::in(PlatformType::getValues())],
            'features' => ['required', 'array'],
            'open_fee' => ['required', 'numeric', 'min:0'],
            'range_min_top_up' => ['required', 'numeric', 'min:0'],
            'top_up_fee' => ['required', 'numeric', 'min:0'],
            'set_up_time' => ['required', 'numeric', 'min:0'],
            'disabled' => ['required', 'boolean'],
        ],
            [
            'name.required' => __('validation.name_invalid'),
            'name.string' => __('validation.name_invalid'),
            'description.required' => __('validation.description_invalid'),
            'description.string' => __('validation.description_invalid'),
            'platform.required' => __('validation.platform_invalid'),
            'platform.in' => __('validation.platform_invalid'),
            'features.required' => __('validation.features_invalid'),
            'open_fee.required' => __('validation.open_fee_invalid'),
            'range_min_top_up.required' => __('validation.range_min_top_up_invalid'),
            'top_up_fee.required' => __('validation.top_up_fee_invalid'),
            'set_up_time.required' => __('validation.set_up_time_invalid'),
            'disabled.required' => __('validation.disabled_invalid'),
            'disabled.boolean' => __('validation.disabled_invalid'),
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
            if ($platformEnum === PlatformType::GOOGLE) {
                $validCases = ServicePackageFeature::getFeaturesByPlatform('google');
            }else{
                $validCases = ServicePackageFeature::getFeaturesByPlatform('meta');
            }
            // Lấy các keys hợp lệ cho platform này từ Enum
            $validKeys = array_map(fn($case) => $case->value, $validCases);
            // Lấy các keys mà người dùng đã submit
            $submittedKeys = array_column($features, 'key');
            // Tìm các keys không hợp lệ (có trong $submittedKeys nhưng không có trong $validKeys)
            $invalidKeys = array_diff($submittedKeys, $validKeys);
            if (!empty($invalidKeys)) {
                $firstInvalidKey = array_values($invalidKeys)[0];
                // Thêm lỗi vào trường 'platform' (hoặc 'features' tùy bạn)
                // Inertia sẽ nhận lỗi này và hiển thị ở frontend
                $validator->errors()->add(
                    'platform',
                    __('validation.features_invalid')
                );
            }
        });

        // Validate dữ liệu
        $validator->validate();

        // Lấy dữ liệu đã validate
        $form = $validator->validated();

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

    public function editView(string $id): \Inertia\Response
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
                'service_package' => fn () => new ServicePackageResource($result->getData()),
            ]
        );
    }

    public function update(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(),
            [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:255'],
                'platform' => ['required', Rule::in(PlatformType::getValues())],
                'features' => ['required', 'array'],
                'open_fee' => ['required', 'numeric', 'min:0'],
                'range_min_top_up' => ['required', 'numeric', 'min:0'],
                'top_up_fee' => ['required', 'numeric', 'min:0'],
                'set_up_time' => ['required', 'numeric', 'min:0'],
                'disabled' => ['required', 'boolean'],
            ],
            [
                'name.required' => __('validation.name_invalid'),
                'name.string' => __('validation.name_invalid'),
                'description.required' => __('validation.description_invalid'),
                'description.string' => __('validation.description_invalid'),
                'platform.required' => __('validation.platform_invalid'),
                'platform.in' => __('validation.platform_invalid'),
                'features.required' => __('validation.features_invalid'),
                'open_fee.required' => __('validation.open_fee_invalid'),
                'range_min_top_up.required' => __('validation.range_min_top_up_invalid'),
                'top_up_fee.required' => __('validation.top_up_fee_invalid'),
                'set_up_time.required' => __('validation.set_up_time_invalid'),
                'disabled.required' => __('validation.disabled_invalid'),
                'disabled.boolean' => __('validation.disabled_invalid'),
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
            if ($platformEnum === PlatformType::GOOGLE) {
                $validCases = ServicePackageFeature::getFeaturesByPlatform('google');
            }else{
                $validCases = ServicePackageFeature::getFeaturesByPlatform('meta');
            }
            // Lấy các keys hợp lệ cho platform này từ Enum
            $validKeys = array_map(fn($case) => $case->value, $validCases);
            // Lấy các keys mà người dùng đã submit
            $submittedKeys = array_column($features, 'key');
            // Tìm các keys không hợp lệ (có trong $submittedKeys nhưng không có trong $validKeys)
            $invalidKeys = array_diff($submittedKeys, $validKeys);
            if (!empty($invalidKeys)) {
                $firstInvalidKey = array_values($invalidKeys)[0];
                // Thêm lỗi vào trường 'platform' (hoặc 'features' tùy bạn)
                // Inertia sẽ nhận lỗi này và hiển thị ở frontend
                $validator->errors()->add(
                    'platform',
                    __('validation.features_invalid')
                );
            }
        });

        // Validate dữ liệu
        $validator->validate();

        // Lấy dữ liệu đã validate
        $form = $validator->validated();

        // Cập nhật service package
        $result = $this->servicePackageService->updateServicePackage($id, $form);

        // Xử lý kết quả
        if($result->isSuccess()){
            FlashMessage::success(__('common_success.edit_success'));
            return redirect()->route('service_packages_index');
        }else{
            FlashMessage::error($result->getMessage());
            return redirect()->back()->withInput();
        }
    }

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
}
