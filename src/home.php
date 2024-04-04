<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="css/bootstrap.min.5.0.2.css">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/select2.min.4.1.0.css">
<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script></script>
<!--<script type="text/javascript" src="js/bootstrap.5.0.2.min.js"></script>-->
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
              <input type="checkbox" class="form-check-input" id="ebuGradeSync" disabled>
              <label class="form-check-label" for="ebuGradesync">Sync event with grade item (Attendance)</label>
            </div>
            <div class="mb-3 hidden" id="ebuGradeSyncDiv">
                <label for="gradeItem" class="form-label">Grade Item (must exist)</label>
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
            <tr><th>Event Name</th><th>Starts on</th><th>Actions</th></tr>
            <?php echo  $linkedEvents;?>
            <br>
          </tbody>
          </table>
          
    </div>
<script type="text/javascript" src="js/ble-bu.js"></script>
</body>
</html>