document.addEventListener('DOMContentLoaded', function() {
    const PUSHER_APP_KEY = document.body.getAttribute('data-pusher-key');
    const PUSHER_APP_CLUSTER = document.body.getAttribute('data-pusher-cluster');
    const USER_ID = document.body.getAttribute('data-user-id');
    const BASE_URL = document.body.getAttribute('data-base-url');

    const notificationList = document.getElementById('notification-list');
    const notificationCounter = document.getElementById('notification-count');
    const clearAllBtn = document.getElementById('clear-all-notifications');
    const eventDetailsModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
    const eventDetailsModalBody = document.getElementById('eventDetailsModalBody');
    const userDropdownToggle = document.querySelector('.user-dropdown-toggle');
    
    let notifications = [];

    function updateNotificationUI() {
        notificationList.innerHTML = '';
        if (notifications.length === 0) {
            notificationList.innerHTML = '<li><a class="dropdown-item text-center" href="#">No new notifications</a></li>';
            notificationCounter.style.display = 'none';
        } else {
            notifications.forEach(notif => {
                const li = document.createElement('li');
                
                // Decide if the main container is a link or a div
                const isLink = notif.link && !notif.event_id;
                const mainElementTag = isLink ? 'a' : 'div';
                const href = isLink ? `href="${notif.link}"` : 'href="#"';

                let detailsHtml = '';
                if (notif.event_id) {
                    detailsHtml = `
                    <div class="text-muted small mt-1">
                        <i class="fas fa-map-marker-alt me-1"></i>${notif.venue || 'N/A'}
                        <i class="fas fa-clock ms-2 me-1"></i>${notif.start_date || 'N/A'}
                    </div>`;
                }

                li.innerHTML = `
                    <div class="dropdown-item notification-item-container">
                        <${mainElementTag} class="notification-item-content" ${href} data-event-id="${notif.event_id || ''}" data-notification-id="${notif.id}">
                            <div>${notif.message}</div>
                            ${detailsHtml}
                        </${mainElementTag}>
                        <button class="btn btn-sm btn-outline-secondary mark-as-read ms-2" title="Mark as read" data-notification-id="${notif.id}">
                                <i class="fas fa-check"></i>
                            </button>
                    </div>`;
                notificationList.appendChild(li);
            });
            notificationCounter.textContent = notifications.length;
            notificationCounter.style.display = 'block';
        }
        // Re-add the divider and clear all button
        notificationList.insertAdjacentHTML('beforeend', '<li><hr class="dropdown-divider"></li>');
        notificationList.appendChild(clearAllBtn.parentElement);
    }

    async function fetchInitialNotifications() {
        try {
            const response = await fetch(`${BASE_URL}/api/notifications.php?action=get_initial_notifications`);
            if (response.ok) {
                const data = await response.json();
                notifications = data;
                updateNotificationUI();
            } else {
                console.error('Failed to fetch initial notifications');
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }
    
    async function showEventDetails(eventId) {
        eventDetailsModalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        eventDetailsModal.show();
        try {
            const response = await fetch(`${BASE_URL}/api/events.php?action=get_event_details&id=${eventId}`);
            if (response.ok) {
                const event = await response.json();
                eventDetailsModalBody.innerHTML = `
                    <h4>${event.title}</h4>
                    <p><strong>Description:</strong> ${event.description}</p>
                    <p><strong>Category:</strong> ${event.category}</p>
                    <p><strong>Venue:</strong> ${event.venue_name}</p>
                    <p><strong>Starts:</strong> ${event.start_date_formatted}</p>
                    <p><strong>Ends:</strong> ${event.end_date_formatted}</p>
                    <p><strong>Max Participants:</strong> ${event.max_participants}</p>
                     <p><strong>Created By:</strong> ${event.creator_name}</p>
                    <p><strong>Status:</strong> <span class="badge bg-primary">${event.status}</span></p>
                `;
            } else {
                 eventDetailsModalBody.innerHTML = '<p class="text-danger">Could not load event details.</p>';
            }
        } catch (error) {
            console.error('Error fetching event details:', error);
            eventDetailsModalBody.innerHTML = '<p class="text-danger">An error occurred while fetching event details.</p>';
        }
    }

    async function markNotificationAsRead(notificationId) {
        try {
            const response = await fetch(`${BASE_URL}/api/notifications.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_as_read', id: notificationId })
            });
            if (response.ok) {
                notifications = notifications.filter(n => n.id.toString() !== notificationId.toString());
                updateNotificationUI();
            } else {
                console.error('Failed to mark notification as read');
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async function clearAllNotifications() {
        try {
            const response = await fetch(`${BASE_URL}/api/notifications.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'clear_all' })
            });
            if (response.ok) {
                notifications = [];
                updateNotificationUI();
            } else {
                console.error('Failed to clear all notifications');
            }
        } catch (error) {
            console.error('Error clearing notifications:', error);
        }
    }
    
    // Event Listeners
    notificationList.addEventListener('click', function(e) {
        const target = e.target;
        
        const markReadButton = target.closest('.mark-as-read');
        if (markReadButton) {
            e.preventDefault();
            e.stopPropagation();
            const notificationId = markReadButton.dataset.notificationId;
            markNotificationAsRead(notificationId);
            return;
        }

        const notificationItem = target.closest('.notification-item-content');
        if (notificationItem) {
            // Only prevent default if it's NOT a direct link
            if (!notificationItem.href || !notificationItem.href.endsWith('#')) {
                // This is a real link, let the browser handle it.
                // We might want to mark as read before navigating.
                const notificationId = notificationItem.dataset.notificationId;
                markNotificationAsRead(notificationId);
                return; 
            }
            
            e.preventDefault();
            const eventId = notificationItem.dataset.eventId;
            if (eventId) {
            showEventDetails(eventId);
            }
        }
    });

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (notifications.length > 0) {
                clearAllNotifications();
            }
        });
    }

    // Pusher setup
    if (PUSHER_APP_KEY && USER_ID) {
        const pusher = new Pusher(PUSHER_APP_KEY, {
            cluster: PUSHER_APP_CLUSTER,
            encrypted: true,
            authEndpoint: `${BASE_URL}/api/pusher_auth.php`
        });

        const channelName = `private-user-notifications-${USER_ID}`;
        const channel = pusher.subscribe(channelName);

        pusher.connection.bind('connected', () => {
             console.log('Pusher connected successfully to ' + channelName);
             // Store the socket ID to exclude the sender from receiving their own notification
             const socketId = pusher.connection.socket_id;
             const socketIdInputs = document.querySelectorAll('input[name="socket_id"]');
             socketIdInputs.forEach(input => input.value = socketId);
        });
        
        pusher.connection.bind('error', function(err) {
            console.error('Pusher connection error:', err);
        });

        channel.bind('new-notification', function(data) {
            notifications.unshift(data); // Add to the beginning of the array
            updateNotificationUI();
        });
    } else {
        console.warn('Pusher credentials or User ID not found. Real-time notifications disabled.');
    }

    // Initial fetch
    fetchInitialNotifications();
}); 