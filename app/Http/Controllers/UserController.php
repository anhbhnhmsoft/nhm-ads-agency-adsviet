<?php

namespace App\Http\Controllers;

use App\Common\Constants\User\UserRole;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Http\Requests\User\ToggleDisableRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\ListCustomerResource;
use App\Http\Resources\ListEmployeeResource;
use App\Service\UserService;
use App\Service\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class UserController extends Controller
{

    public function __construct(
        protected UserService $userService,
        protected WalletService $walletService,
    ) {

    }

    public function listCustomer(Request $request)
    {
        $data = [];
        $authUser = $request->user();
        
        // Xử lý wallet nếu có wallet_user_id trong query
        if ($request->has('wallet_user_id')) {
            $userId = $request->string('wallet_user_id')->toString();
            $walletResult = $this->walletService->getWalletForUser($userId);
            if ($walletResult->isSuccess()) {
                $data['wallet'] = $walletResult->getData();
            } else {
                $data['walletError'] = $walletResult->getMessage();
            }
        }
        
        $params = $this->extractQueryPagination($request);
        $result = $this->userService->getListCustomerPagination(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));
        $paginator = $result->getData();

        $managers = [];
        $employees = [];
        $canFilterManager = false;
        $canFilterEmployee = false;

        if ($authUser && $authUser->role === UserRole::ADMIN->value) {
            $canFilterManager = true;
            $canFilterEmployee = true;
            $managersResult = $this->userService->getManagers();
            $employeesResult = $this->userService->getEmployeesSimple();
            $managers = $managersResult->isSuccess() ? $managersResult->getData() : [];
            $employees = $employeesResult->isSuccess() ? $employeesResult->getData() : [];
        } elseif ($authUser && $authUser->role === UserRole::MANAGER->value) {
            $canFilterEmployee = true;
            $employeesResult = $this->userService->getEmployeesAssignedToManager((string) $authUser->id);
            $employees = $employeesResult->isSuccess() ? $employeesResult->getData() : [];
        }

        $data['paginator'] = fn () => ListCustomerResource::collection($paginator);
        $data['managers'] = $managers;
        $data['employees'] = $employees;
        $data['filters'] = $params->get('filter') ?? [];
        $data['canFilterManager'] = $canFilterManager;
        $data['canFilterEmployee'] = $canFilterEmployee;
        $data['filters'] = $params->get('filter') ?? [];

        return $this->rendering(
            view: 'user/list-customer',
            data: $data,
        );
    }

    public function listEmployee(Request $request)
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->userService->getListEmployeePagination(
            queryListDTO: new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $params->get('filter'),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            ),
        );
        $paginator = $result->getData();

        $managersResult = $this->userService->getManagers();
        $managers = $managersResult->isSuccess() ? $managersResult->getData() : [];

        return $this->rendering(
            view: 'user/list-employee',
            data: [
                'paginator' => fn () => ListEmployeeResource::collection($paginator),
                'managers' => $managers,
            ]
        );
    }

    public function createEmployeeScreen(Request $request)
    {
        return $this->rendering(
            view: 'user/create-employee',
        );
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->userService->createEmployee($validated);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.add_success'));
            return redirect()->route('user_list_employee');
        }
        FlashMessage::error($result->getMessage());
        return redirect()->back()->withInput();
    }

    public function update(string $id, UserUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->userService->updateEmployee($id, $validated);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
            return redirect()->route('user_list_employee');
        }
        FlashMessage::error($result->getMessage());
        return redirect()->back()->withInput();
    }

    public function updateUser(string $id, UserUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->userService->updateUser($id, $validated);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
            return redirect()->route('user_list');
        }
        FlashMessage::error($result->getMessage());
        return redirect()->back()->withInput();
    }

    public function destroy(string $id): RedirectResponse
    {
        $result = $this->userService->deleteEmployee($id);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.delete_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->route('user_list_employee');
    }

    public function destroyUser(string $id): RedirectResponse
    {
        $result = $this->userService->deleteUser($id);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.delete_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->route('user_list');
    }

    public function toggleDisable(string $id, ToggleDisableRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->userService->toggleDisable($id, (bool)$validated['disabled']);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->route('user_list_employee');
    }

    public function userToggleDisable(string $id, ToggleDisableRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->userService->userToggleDisable($id, (bool)$validated['disabled']);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->route('user_list');
    }

    public function editEmployeeScreen(string $id)
    {
        $result = $this->userService->findEmployee($id);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->route('user_list_employee');
        }
        return $this->rendering('user/create-employee', [
            'employee' => $result->getData(),
        ]);
    }

    public function editUserScreen(string $id)
    {
        $result = $this->userService->findUser($id);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->route('user_list');
        }
        return $this->rendering('user/create-user', [
            'user' => $result->getData(),
        ]);
    }

    public function getEmployeesByManager(Request $request, string $managerId)
    {
        $result = $this->userService->getEmployeesForAssignment($managerId);
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }
        return response()->json(['success' => true, 'data' => $result->getData()]);
    }

    public function assignEmployee(Request $request)
    {
        $request->validate([
            'manager_id' => 'required|string',
            'employee_id' => 'required|string',
        ]);

        $result = $this->userService->assignEmployee((string)$request->manager_id, (string)$request->employee_id);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }
        FlashMessage::success(__('user.assign_employee_success', ['default' => 'Gán nhân viên thành công']));
        return response()->json(['success' => true]);
    }

    public function unassignEmployee(Request $request)
    {
        $request->validate([
            'manager_id' => 'required|string',
            'employee_id' => 'required|string',
        ]);

        $result = $this->userService->unassignEmployee((string)$request->manager_id, (string)$request->employee_id);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }
        FlashMessage::success(__('user.unassign_employee_success', ['default' => 'Hủy gán nhân viên thành công']));
        return response()->json(['success' => true]);
    }
}
