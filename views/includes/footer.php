        </div> <!-- This closes the container-fluid p-4 from header -->
    </div> <!-- This closes the #content div from header -->
</div> <!-- This closes the .wrapper div from header -->

<footer class="main-footer">
    <div class="container-fluid">
        <p class="text-center text-muted">&copy; <?php echo date('Y'); ?> Campus Event Management. All rights reserved.</p>
    </div>
</footer>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventDetailsModalLabel">Event Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
            <div class="modal-body">
                <!-- Content will be loaded here via AJAX -->
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Pusher JS -->
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo base_url('assets/js/main.js'); ?>"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('sidebarCollapse').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });
    });
</script>

</body>
</html> 