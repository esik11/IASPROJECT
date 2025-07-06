<?php

// Defines permissions for each role based on the RBAC matrix.
// This configuration is corrected to match the matrix exactly.
return [
    'admin' => [
        'submit_event_idea',
        'create_official_event',
        'edit_event',
        'delete_event',
        'cancel_event',
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
        'manage_venue_reservations',
        'view_all_reservation_history', // Can see all reservations
        'manage_event_ideas',
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
        'view_reservation_history',
        'view_all_reservation_history', // Can see all reservations
    ],
    'approver' => [ // Approver (Dean/Head) - CORRECTED
        'approve_reject_events',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
    ],
    'student' => [
        'submit_event_idea',
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'cancel_reservation',
        'view_announcements',
        'view_reservation_history', // Can only see own reservations
    ],
    'faculty' => [
        'create_official_event',
        'edit_event',
        'reserve_venue',
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'cancel_reservation',
        'view_announcements',
        'view_reservation_history', // Can only see own reservations
    ],
    'security_officer' => [
        'assign_staff',
        'upload_documents',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'view_reservation_history',
        'view_all_reservation_history', // Can see all reservations
    ],
    'maintenance_staff' => [
        'assign_staff',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'view_reservation_history',
        'view_all_reservation_history', // Can see all reservations
    ],
    'finance_officer' => [
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'generate_event_reports',
        'view_reservation_history',
        'view_all_reservation_history', // Can see all reservations
    ],
    'guest' => [ // Guest User - CORRECTED
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
    ],
    'auditor' => [
        'approve_reject_events',
        'download_documents',
        'view_event_calendar',
        'view_event_details',
        'view_announcements',
        'generate_event_reports',
        'view_reservation_history',
        'view_activity_logs',
        'view_all_reservation_history', // Can see all reservations
    ],
]; 