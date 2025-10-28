<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class DocsController extends Controller
{
    public function apiJson()
    {
        $routes = $this->collectApiRoutes();
        return response()->json([
            'routes' => $routes,
            'examples' => $this->examples(),
        ]);
    }

    public function apiHtml()
    {
        $routes = $this->collectApiRoutes();
        $examples = $this->examples();
        $exampleMap = $this->exampleMap($examples);
        $descriptions = $this->descriptions();
        return view('api-docs', [
            'routes' => $routes,
            'examples' => $examples,
            'exampleMap' => $exampleMap,
            'descriptions' => $descriptions,
        ]);
    }

    private function collectApiRoutes(): array
    {
        $list = [];
        foreach (RouteFacade::getRoutes() as $route) {
            /** @var Route $route */
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/')) {
                $list[] = [
                    'methods' => array_values(array_diff($route->methods(), ['HEAD'])),
                    'uri' => '/' . $uri,
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'middleware' => $route->gatherMiddleware(),
                ];
            }
        }
        usort($list, function ($a, $b) {
            return strcmp($a['uri'], $b['uri']) ?: strcmp(implode('|', $a['methods']), implode('|', $b['methods']));
        });
        return $list;
    }

    private function examples(): array
    {
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->first();
        $adminEmail = $admin?->email ?: 'admin@example.com';

        $someUser = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->first();
        $someUserId = $someUser?->id ?: 1;

        $firstRole = Role::first()?->name ?: 'user';
        $firstPerm = Permission::first()?->name ?: 'export reports';

        return [
            [
                'title' => 'Đăng nhập (lấy token)',
                'method' => 'POST',
                'uri' => '/api/auth/login',
                'body' => [
                    'email' => $adminEmail,
                    'password' => 'password',
                    'device_name' => 'docs',
                ],
            ],
            [
                'title' => 'Lấy thông tin user hiện tại',
                'method' => 'GET',
                'uri' => '/api/user',
                'body' => (object)[],
                'auth' => true,
            ],
            [
                'title' => 'Tạo role',
                'method' => 'POST',
                'uri' => '/api/roles',
                'body' => ['name' => 'editor'],
                'auth' => true,
            ],
            [
                'title' => 'Tạo permission',
                'method' => 'POST',
                'uri' => '/api/permissions',
                'body' => ['name' => 'publish articles'],
                'auth' => true,
            ],
            [
                'title' => 'Gán role cho user',
                'method' => 'POST',
                'uri' => "/api/users/{$someUserId}/roles",
                'body' => ['role' => $firstRole],
                'auth' => true,
            ],
            [
                'title' => 'Gán permission cho role',
                'method' => 'POST',
                'uri' => "/api/roles/{$firstRole}/permissions",
                'body' => ['permission' => $firstPerm],
                'auth' => true,
            ],
        ];
    }

    private function exampleMap(array $examples): array
    {
        $map = [];
        foreach ($examples as $ex) {
            $key = strtoupper($ex['method']).' '.$ex['uri'];
            $map[$key] = [
                'body' => $ex['body'] ?? (object)[],
                'auth' => $ex['auth'] ?? false,
            ];
        }
        return $map;
    }

    private function descriptions(): array
    {
        return [
            'POST /api/auth/register' => 'Đăng ký tài khoản mới và trả về token.',
            'POST /api/auth/login' => 'Đăng nhập và phát hành Sanctum token (Bearer).',
            'POST /api/auth/logout' => 'Thu hồi token hiện tại của người dùng.',
            'GET /api/user' => 'Lấy thông tin người dùng hiện tại từ token.',

            'GET /api/roles' => 'Liệt kê tất cả role.',
            'POST /api/roles' => 'Tạo role mới.',
            'DELETE /api/roles/{role}' => 'Xoá role theo tên.',

            'GET /api/permissions' => 'Liệt kê tất cả permission.',
            'POST /api/permissions' => 'Tạo permission mới.',
            'DELETE /api/permissions/{permission}' => 'Xoá permission theo tên.',

            'POST /api/users/{userId}/roles' => 'Gán role cho user.',
            'DELETE /api/users/{userId}/roles/{role}' => 'Gỡ role khỏi user.',
            'POST /api/users/{userId}/permissions' => 'Gán permission trực tiếp cho user.',
            'DELETE /api/users/{userId}/permissions/{permission}' => 'Thu hồi permission trực tiếp của user.',

            'POST /api/roles/{role}/permissions' => 'Gán permission cho role.',
            'DELETE /api/roles/{role}/permissions/{permission}' => 'Gỡ permission khỏi role.',

            'GET /api/admin/overview' => 'Ví dụ endpoint chỉ dành cho role admin.',
            'GET /api/reports/export' => 'Ví dụ endpoint yêu cầu permission export reports.',
        ];
    }
}


