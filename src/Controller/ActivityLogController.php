<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ActivityLogController extends AbstractController
{
    #[Route('/admin/activity-logs', name: 'app_activity_logs')]
    public function index(
        Request $request,
        ActivityLogRepository $logRepository,
        UserRepository $userRepository
    ): Response {
        // Ensure user is admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get filter parameters
        $userId = $request->query->get('user_id');
        $action = $request->query->get('action');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $page = (int) $request->query->get('page', 1);

        // Parse dates if provided
        $dateFromObj = null;
        $dateToObj = null;

        if ($dateFrom) {
            try {
                $dateFromObj = new \DateTime($dateFrom . ' 00:00:00');
            } catch (\Exception $e) {
                // Invalid date, ignore
            }
        }

        if ($dateTo) {
            try {
                $dateToObj = new \DateTime($dateTo . ' 23:59:59');
            } catch (\Exception $e) {
                // Invalid date, ignore
            }
        }

        // Pagination
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Fetch logs
        $logs = $logRepository->findByFilters(
            $userId ? (int) $userId : null,
            $action ?: null,
            $dateFromObj,
            $dateToObj,
            $limit,
            $offset
        );

        // Count total for pagination
        $total = $logRepository->countByFilters(
            $userId ? (int) $userId : null,
            $action ?: null,
            $dateFromObj,
            $dateToObj
        );

        $totalPages = ceil($total / $limit);

        // Get all users for filter dropdown
        $users = $userRepository->findAll();

        return $this->render('admin/activity_logs.html.twig', [
            'logs' => $logs,
            'users' => $users,
            'selected_user_id' => $userId,
            'selected_action' => $action,
            'selected_date_from' => $dateFrom,
            'selected_date_to' => $dateTo,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $total,
            'available_actions' => ActivityLog::AVAILABLE_ACTIONS,
        ]);
    }

    #[Route('/admin/activity-logs/{id}', name: 'app_activity_log_show')]
    public function show(ActivityLog $log): Response
    {
        // Ensure user is admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/activity_log_detail.html.twig', [
            'log' => $log,
        ]);
    }
}
