<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Events Calendar') }}
            </h2>
            <a href="{{ route('events.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Event
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Calendar Controls -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h3 class="h4 mb-0">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                Events Calendar
                            </h3>
                            <p class="text-muted mb-0">Manage and view all your events</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="calendar-today">
                                    <i class="fas fa-calendar-day me-1"></i>Today
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="calendar-prev">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="calendar-next">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar Container -->
                    <div class="card">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>

                    <!-- Event Details Modal -->
                    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="eventDetails">
                                        <!-- Event details will be loaded here -->
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <a href="#" class="btn btn-primary" id="editEventBtn">
                                        <i class="fas fa-edit me-2"></i>Edit Event
                                    </a>
                                    <button type="button" class="btn btn-danger" id="deleteEventBtn">
                                        <i class="fas fa-trash me-2"></i>Delete Event
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Include FullCalendar CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        let calendar;

        if (calendarEl) {
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: '',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                height: 'auto',
                editable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                weekends: true,
                nowIndicator: true,
                
                // Load events from API
                events: {
                    url: '{{ route("api.calendar.events") }}',
                    method: 'GET',
                    failure: function() {
                        alert('Failed to load events. Please refresh the page.');
                    }
                },

                // Handle date/time selection for creating new events
                select: function(selectionInfo) {
                    const startDate = selectionInfo.start;
                    const startDateStr = startDate.toISOString().split('T')[0]; // YYYY-MM-DD format
                    const startTimeStr = startDate.toTimeString().split(' ')[0].substring(0, 5); // HH:MM format
                    
                    // Redirect to create event page with pre-filled date/time
                    const createUrl = new URL('{{ route("events.create") }}');
                    createUrl.searchParams.set('start_date', startDateStr);
                    createUrl.searchParams.set('start_hour', startTimeStr);
                    
                    window.location.href = createUrl.toString();
                },

                // Handle event clicks (view/edit existing events)
                eventClick: function(eventClickInfo) {
                    const event = eventClickInfo.event;
                    
                    // Populate modal with event details
                    const eventDetails = `
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="text-primary mb-3">
                                    <i class="fas fa-calendar-check me-2"></i>${event.title}
                                </h4>
                                <div class="mb-3">
                                    <strong><i class="fas fa-clock me-2"></i>Date & Time:</strong><br>
                                    <span class="badge bg-primary me-2">
                                        ${event.start ? event.start.toLocaleDateString() : 'No date'}
                                    </span>
                                    <span class="badge bg-secondary">
                                        ${event.start ? event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'No time'} - 
                                        ${event.end ? event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'No end time'}
                                    </span>
                                </div>
                                ${event.extendedProps.description ? `
                                    <div class="mb-3">
                                        <strong><i class="fas fa-align-left me-2"></i>Description:</strong><br>
                                        <p class="text-muted">${event.extendedProps.description}</p>
                                    </div>
                                ` : ''}
                                ${event.extendedProps.venue ? `
                                    <div class="mb-3">
                                        <strong><i class="fas fa-map-marker-alt me-2"></i>Venue:</strong>
                                        <span class="ms-2">${event.extendedProps.venue}</span>
                                    </div>
                                ` : ''}
                                ${event.extendedProps.event_type ? `
                                    <div class="mb-3">
                                        <strong><i class="fas fa-tag me-2"></i>Event Type:</strong>
                                        <span class="badge bg-info ms-2">${event.extendedProps.event_type}</span>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="col-md-4">
                                ${event.extendedProps.price ? `
                                    <div class="mb-3">
                                        <strong><i class="fas fa-dollar-sign me-2"></i>Price:</strong>
                                        <span class="ms-2">${event.extendedProps.price}</span>
                                    </div>
                                ` : ''}
                                ${event.extendedProps.capacity ? `
                                    <div class="mb-3">
                                        <strong><i class="fas fa-users me-2"></i>Capacity:</strong>
                                        <span class="ms-2">${event.extendedProps.capacity}</span>
                                    </div>
                                ` : ''}
                                ${event.extendedProps.contact_person ? `
                                    <div class="mb-3">
                                        <strong><i class="fas fa-user me-2"></i>Contact:</strong><br>
                                        <span>${event.extendedProps.contact_person}</span>
                                        ${event.extendedProps.phone_no ? `<br><small class="text-muted">${event.extendedProps.phone_no}</small>` : ''}
                                        ${event.extendedProps.email ? `<br><small class="text-muted">${event.extendedProps.email}</small>` : ''}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('eventDetails').innerHTML = eventDetails;
                    document.getElementById('editEventBtn').href = event.extendedProps.url || '#';
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('eventModal'));
                    modal.show();
                },

                // Handle event drag and drop (update event dates)
                eventDrop: function(eventDropInfo) {
                    const event = eventDropInfo.event;
                    
                    // Here you could add AJAX call to update event in database
                    console.log('Event moved:', {
                        id: event.id,
                        start: event.start,
                        end: event.end
                    });
                    
                    // Show success message
                    showNotification('Event updated successfully!', 'success');
                },

                // Handle event resize (update event duration)
                eventResize: function(eventResizeInfo) {
                    const event = eventResizeInfo.event;
                    
                    // Here you could add AJAX call to update event duration in database
                    console.log('Event resized:', {
                        id: event.id,
                        start: event.start,
                        end: event.end
                    });
                    
                    // Show success message
                    showNotification('Event duration updated!', 'success');
                }
            });

            calendar.render();

            // Custom toolbar button handlers
            document.getElementById('calendar-today').addEventListener('click', function() {
                calendar.today();
            });

            document.getElementById('calendar-prev').addEventListener('click', function() {
                calendar.prev();
            });

            document.getElementById('calendar-next').addEventListener('click', function() {
                calendar.next();
            });

            // Delete event handler
            document.getElementById('deleteEventBtn').addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this event?')) {
                    // Here you would add AJAX call to delete event
                    const modal = bootstrap.Modal.getInstance(document.getElementById('eventModal'));
                    modal.hide();
                    showNotification('Event deleted successfully!', 'success');
                    calendar.refetchEvents();
                }
            });
        }

        // Notification helper function
        function showNotification(message, type = 'info') {
            // Create a simple notification (you can enhance this with a proper notification library)
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
    });
    </script>
</x-app-layout>