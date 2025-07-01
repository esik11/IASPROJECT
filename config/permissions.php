<?php

// Defines permissions for each role based on the RBAC matrix.
return [
    'admin' => [
        'submit_event_idea',
        'create_official_event',
        'edit_event',
        'delete_event',
        'reserve_venue',
        'approve_reject_events',
        'assign_staff',
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'cancel_reservation',
        'post_announcements',
        'view_announcements',
        'notify_assigned_staff',
        'generate_event_reports',
        'manage_users',
        'manage_venues',
        'view_reservation_history',
        'view_activity_logs',
        'clear_activity_logs'
    ],
    'event_coordinator' => [
        'create_official_event',
        'reserve_venue',
        'assign_staff',
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'post_announcements',
        'view_announcements',
        'notify_assigned_staff',
        'generate_event_reports',
        'view_reservation_history'
    ],
    'approver' => [ // Approver (Dean/Head)
        'approve_reject_events',
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements'
    ],
    'student' => [
        'submit_event_idea',
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'view_reservation_history'
    ],
    'faculty' => [
        'submit_event_idea',
        'create_official_event',
        'edit_event',
        'reserve_venue',
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'view_reservation_history'
    ],
    'security_officer' => [
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'view_reservation_history'
    ],
    'maintenance_staff' => [
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'view_reservation_history'
    ],
    'finance_officer' => [
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'generate_event_reports',
        'view_reservation_history'
    ],
    'guest' => [ // Guest User
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements'
    ],
    'auditor' => [
        'approve_reject_events',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'generate_event_reports',
        'view_reservation_history',
        'view_activity_logs'
    ],
]; 