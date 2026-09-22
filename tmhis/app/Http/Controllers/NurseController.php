<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NurseController extends Controller
{
    private function resolveNurse()
    {
        $user = auth()->user();
        if ($user) {
            $_SESSION['user_id'] = $user->UserID;
            $_SESSION['nurse_id'] = $user->UserID;
            $_SESSION['username'] = $user->Username;
            $_SESSION['full_name'] = $user->FullName;
            $_SESSION['role'] = 'Nurse';
            $_SESSION['email'] = $user->Email;
            $_SESSION['department'] = 'General Ward & Triage';
            $_SESSION['shift'] = 'Day Shift (6:00 AM – 2:00 PM)';
        }
    }

    public function dashboard(Request $request)
    {
        return $this->handlePage($request, 'dashboard');
    }

    public function handleLegacyView(Request $request, $path = '')
    {
        $path = trim($path, '/');
        $parts = explode('/', $path);
        $module = strtolower($parts[0] ?? 'dashboard');
        $file = strtolower($parts[1] ?? 'index.php');

        $sub = null;
        if (str_contains($file, 'view') || ($module === 'patients' && $request->has('id'))) {
            $sub = 'view';
        }

        if ($request->isMethod('get')) {
            $cleanUrl = match($module) {
                'dashboard' => '/nurse',
                'vitals' => '/nurse/vitals',
                'patients' => ($sub === 'view') ? '/nurse/patients/view' : '/nurse/patients',
                'queue' => '/nurse/queue',
                'tasks' => '/nurse/tasks',
                'notifications' => '/nurse/notifications',
                'settings' => '/nurse/settings',
                default => '/nurse'
            };
            if ($request->getQueryString()) {
                $cleanUrl .= '?' . $request->getQueryString();
            }
            return redirect($cleanUrl);
        }

        return $this->handlePage($request, $module, $sub);
    }

    public function handlePage(Request $request, $page = 'dashboard', $sub = null)
    {
        $this->resolveNurse();

        $page = strtolower(trim((string)$page, '/'));
        $sub = strtolower(trim((string)$sub, '/'));

        // Normalize legacy path structures
        if (str_starts_with($page, 'views/')) {
            $page = substr($page, 6);
        }
        if ($page === 'views' && !empty($sub)) {
            $page = $sub;
            $sub = null;
        }
        $page = preg_replace('/(\.php|\/index\.php|\/index)$/', '', $page);
        $sub = preg_replace('/(\.php|\/index\.php|\/index)$/', '', $sub);

        $targetFile = match($page) {
            'dashboard' => resource_path('views/nurse/dashboard/index.php'),
            'vitals' => resource_path('views/nurse/vitals/index.php'),
            'patients' => ($sub === 'view' || $request->has('id')) ? resource_path('views/nurse/patients/view.php') : resource_path('views/nurse/patients/index.php'),
            'queue' => resource_path('views/nurse/queue/index.php'),
            'tasks' => resource_path('views/nurse/tasks/index.php'),
            'notifications' => resource_path('views/nurse/notifications/index.php'),
            'settings' => resource_path('views/nurse/settings/index.php'),
            default => resource_path('views/nurse/dashboard/index.php')
        };

        if (!file_exists($targetFile)) {
            abort(404, "Nurse page '$page' not found.");
        }

        $_SERVER['REQUEST_METHOD'] = $request->method();
        $_GET = $request->query->all();
        if ($request->isMethod('post')) {
            $_POST = $request->request->all();
        }

        ob_start();
        include $targetFile;
        $content = ob_get_clean();
        return response($content);
    }
}
