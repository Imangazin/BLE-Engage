let ebuGradeSyncCheck = document.getElementById("ebuGradeSync");
let divHidden = document.getElementById("ebuGradeSyncDiv");
let gradeItem = document.getElementById("gradeItem");
let orgSelectTag = document.getElementById("ebuOrganization");
let eventSelectTag = document.getElementById("ebuEvent");
let responseContainer = document.getElementById("responseContainer");
const rowsPerPage = 10;
let currentPage = 1;


$(document).ready(function() {
  $('.select2').select2({
    placeholder: 'Select an option', // Placeholder text
    width: '100%', // Adjust the width as needed
  });
  // Form submit: Creates a new section and enrolls engage users to current org unit
  $('#ebuForm').submit(function(event) {
    event.preventDefault();
    var formData = $(this).serialize();
    responseContainer.innerHTML = '<img src="img/loading.gif" alt="Loading...">';
    // Make the POST request
    $.post('src/toolInteract.php', formData, function(response) {
      responseContainer.className = '';
      responseContainer.className = 'alert alert-success';
      responseContainer.innerHTML = response;
      responseContainer.focus();
      document.getElementById("ebuForm").reset();
      $('#ebuOrganization').val(null).trigger('change');
      $('#ebuEvent').val(null).trigger('change');
      $('#gradeItem').val(null).trigger('change');
      reloadPageAfterDelay(1000);
    }).fail(function(xhr, status, error) {
      console.error('Error submitting form:', error);
      responseContainer.className = '';
      responseContainer.className = 'alert alert-danger';

      responseContainer.innerHTML = response;
      responseContainer.focus();
    });
  });

  // event delete form submit
  $("#deleteEventButton").click(function(){
    var requestData = {
      sectionId: document.getElementById("sessionIdToBedeleted").value
    };
    $.post('src/toolInteract.php', requestData, function(response){
      $('#deleteConfirmModal').modal('hide');
      location.reload();
    }
    ).fail(function(xhr, status, error) {
      console.error('Error submitting delete form:', error);
    });
  });

  //Load BU Organizations
  $.get('src/toolInteract.php', function (data) {
    eventSelectTag.innerHTML = '<option></option>';
    gradeItem.innerHTML = '<option></option>';
    data = JSON.parse(data); 
    data.forEach(function(each){
      const optionElement = document.createElement("option");
      optionElement.value = each.id;
      optionElement.text = each.name;
      orgSelectTag.appendChild(optionElement);
    });
  }).fail(function (xhr, status, error) {
    console.error('GET request failed:', status, error);
  });

    //Setup table and pagination
    setupTablePagination()
});

  //Load BU events for given organization
$('#ebuOrganization').on('select2:select', function (e) {
  eventSelectTag.innerHTML = '<option></option>';
  const selectedValue = e.params.data.id;
  const selectedText = e.params.data.text;
  const selectId = $(this).attr('id'); // Get the ID of the changed select
  $.get('src/toolInteract.php?organizationId='+selectedValue, function (data) {
    data = JSON.parse(data); 
    data.forEach(function(each){
      const optionElement = document.createElement("option");
      optionElement.value = each.id;
      const dateTime=formatDateTime(each.startDate);
      optionElement.text = each.name+' ('+dateTime.date+'  @ '+dateTime.time+')';
      eventSelectTag.appendChild(optionElement);
    });
  }).fail(function (xhr, status, error) {
    console.error('GET request failed:', status, error);
  });
});

// formats the UTC date 
function formatDateTime(dateString) {
  const date = new Date(dateString);
  const formattedDate = date.toLocaleDateString(); 
  const formattedTime = date.toLocaleTimeString([], {hour12:true, hour:'2-digit', minute:'2-digit'});
  return {
      date: formattedDate,
      time: formattedTime
  };
}

//Add an event listener to the checkbox
ebuGradeSyncCheck.addEventListener("change", function () {
  if (ebuGradeSyncCheck.checked) {
    gradeItem.innerHTML = '<option></option>';
    $.get('src/toolInteract.php?gradeSyncEnabled=true', function (data) {
      data = JSON.parse(data); 
      data.forEach(function(each){
        const optionElement = document.createElement("option");
        optionElement.value = each.id;
        optionElement.text = each.name;
        gradeItem.appendChild(optionElement);
      });
    }).fail(function (xhr, status, error) {
      console.error('GET request failed:', status, error);
    });
    divHidden.classList.remove("hidden");
    gradeItem.setAttribute('required', '');
  } else {
    divHidden.classList.add("hidden");
    gradeItem.removeAttribute('required');
    gradeItem.innerHTML = '<option></option>';
  }
});

// //looks for the closest row where  the button was clicked and gets the sessionId from the row
function setSessionId(button) {
  // Find the closest row to the button
  var closestRow = $(button).closest('tr');
  var sectionId = $(closestRow).find('td:eq(0)').text();
  document.getElementById("sessionIdToBedeleted").value = sectionId;
}

// Function to reload the page after a delay
function reloadPageAfterDelay(delay) {
  setTimeout(function() {
    location.reload();
  }, delay);
}

//handling update button on the table
function updateEventById(button){
  // Find the closest row to the button
  var closestRow = $(button).closest('tr');
  //var img = $(button).prev('.loading-gif');
  var img = button.parentElement.previousElementSibling;
  img.style.display = 'inline';
  var sectionId = $(closestRow).find('td:eq(0)').text();
  var eventId = $(closestRow).find('td:eq(1)').text();
  var gradeId = $(closestRow).find('td:eq(4)').text();
  var requestData = {
    sectionId: sectionId,
    eventId : eventId,
    gradeId : gradeId
  };
  $.post('src/toolInteract.php?updateEvent=true', requestData, function(response){
    img.style.display = 'none';
    location.reload();
  }
  ).fail(function(xhr, status, error) {
    img.style.display = 'none';
    console.error('Error submitting form:', error);
  });

}


function setupTablePagination(){
  $.get('src/toolInteract.php?tablePrint=1', function (data) {
    data = JSON.parse(data);
    printTable(data, currentPage);
  }).fail(function (xhr, status, error) {
    console.error('Failed to get data to print to table:', status, error);
  });
}

//printing events to table
function printTable(tableData, page) {
  let tableRows = '';
  var tableBody = document.getElementById("eventsList");
  const start = (page - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const paginatedData = tableData.slice(start, end);

  paginatedData.forEach(event=>{
        // Replace null values with empty string
        for (const prop in event) {
          if (event[prop] === null) {
              event[prop] = '';
          }
        }
    tableRows += `<tr>
                    <td style='display:none;'>${event.sectionId}</td>
                    <td style='display:none;'>${event.eventId}</td>
                    <td>${event.eventName}</td>
                    <td>${event.startDate}</td>
                    <td>${event.endDate}</td>
                    <td style='display:none;'>${event.gradeId}</td>
                    <td>${event.gradeObjectName}</td>
                    <td>
                        <div class='action-container'>
                            <span style='font-size:14px; grid-column: 2; grid-row:1;'>Last updated on <br>${event.lastSync}</span>
                            <img src='img/loading.gif' alt='Loading...' class='loading-gif' style='display: none;'>
                            <div class='button-container'>
                                <button type='button' class='btn btn-secondary btn-sm update-btn' onclick='updateEventById(this)'>Update</button>
                                <button type='button' class='btn btn-red btn-sm delete-btn' data-bs-toggle='modal' data-bs-target='#deleteConfirmModal' onclick='setSessionId(this)'>Delete</button>
                            </div>
                        </div>
                    </td>
                  </tr>`;
  });
  console.log(tableBody);
  tableBody.innerHTML = tableRows;
}

// //handling pagination button clicks
// function fetchPage(page) {
//   $.get('src/toolInteract.php?page='+page, function (data) {
//     data = JSON.parse(data); 
//     console.log(data);
//     var tableBody = $('#linked_events tbody');
//     var pageDive = document.getElementById("pagination");
//     tableBody.innerHTML='';
//     tableBody.innerHTML = data['tableRows'];
//     pageDive.innerHTML='';
//     pageDive.innerHTML = data['pagination'];
//     setupPaginationLinks();
//   }).fail(function (xhr, status, error) {
//     console.error('Failed to get paginations:', status, error);
//   });
// }

// //setup pagination
// function setupPaginationLinks() {
//   $('.page-link').on('click', function(e) {
//       e.preventDefault();
//       const page = parseInt($(this).data('page'));
//       console.log(page);
//       if (!isNaN(page)) {
//           fetchPage(page);
//       }
//   });
// }