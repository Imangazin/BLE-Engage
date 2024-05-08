<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="css/bootstrap.min.5.0.2.css">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/select2.min.4.1.0.css">
<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script></script>
<script type="text/javascript" src="js/bootstrap.5.0.2.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script type="text/javascript" src="js/select2-4.1.0.min.js"></script>

</head>
<body>
    <div class="container-fluid"><br>
        <h2>Link experience BU event with this Course offering</h2><br>
        <form id = "ebuForm">
        <input type="hidden" name="session_id" value='<?php echo $session_id; ?>'>
            <div class="mb-3">
              <label for="ebuOrganization" class="form-label">Experience BU Organization</label>
              <select class="form-select select2" id="ebuOrganization" name="ebuOrganization" required>
                <option></option>
              </select>
            </div>
            <div class="mb-3">
                <label for="ebuEvent" class="form-label">Experience BU Event</label>
                <select class="form-select select2" id="ebuEvent" name="ebuEvent" required>
                  <option></option>
                </select>
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="ebuGradeSync">
              <label class="form-check-label" for="ebuGradeSync">Sync event with grade item (Attendance)</label>
            </div>
            <div class="mb-3 hidden" id="ebuGradeSyncDiv">
                <label for="gradeItem" class="form-label">Grade Item (must exist and it supports Numeric or Pass/Fail types)</label>
                <select class="form-select select2" id="gradeItem" name="gradeItem">
                  <option></option>
                </select>
            </div>
            <div class="mb-3">
              <button type="submit" class="btn btn-primary">Sync</button>
            </div>
          </form>
          <div id="responseContainer" tabindex="-1"></div>
          <h2>Linked Engage events</h2><br>
          <table id="linked_events" class="d2l-table">
          <tbody>
            <tr><th style="display:none;"></th><th style="display:none;"></th><th>Event Name</th><th>Starts on</th><th style="display:none;">GradeId</th><th>Grade Item</th><th>Actions</th></tr>
            <?php echo  $linkedEvents;?>
            <br>
          </tbody>
          </table>
          <!-- Modal -->
          <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="ModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <p>Are you sure you want to delete this event?<br>It will unenroll event RSVPs from the course offering as well.</p>
                        <form id="eventDeleteForm">
                          <input type="text" hidden id="sessionIdToBedeleted" name="sessionIdToBedeleted">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="deleteEventButton" class="btn btn-primary">Delete</button>
                    </div>
                </div>
            </div>
          </div>
    </div>
<script type="text/javascript" src="js/ble-bu.js"></script>
</body>
</html>