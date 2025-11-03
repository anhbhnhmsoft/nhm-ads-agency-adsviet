<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Http\Requests\User\ToggleDisableRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\ListEmployeeResource;
use App\Service\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{

    public function __construct(protected UserService $userService)
    {

    }

    public function listEmployee(Request $request)
    {
        $params = $this->extractQueryPagination($request);
        $currentUser = Auth::user();
        $result = $this->userService->getListEmployeePagination(
            queryListDTO: new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $params->get('filter'),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            ),
            currentUser: $currentUser
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
            'manager_id' => 'required|integer',
            'employee_id' => 'required|integer',
        ]);

        $result = $this->userService->assignEmployee($request->manager_id, $request->employee_id);
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
            'manager_id' => 'required|integer',
            'employee_id' => 'required|integer',
        ]);

        $result = $this->userService->unassignEmployee($request->manager_id, $request->employee_id);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }
        FlashMessage::success(__('user.unassign_employee_success', ['default' => 'Hủy gán nhân viên thành công']));
        return response()->json(['success' => true]);
    }
}
