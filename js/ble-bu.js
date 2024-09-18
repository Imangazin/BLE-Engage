let ebuGradeSyncCheck = document.getElementById("ebuGradeSync");
let divHidden = document.getElementById("ebuGradeSyncDiv");
let gradeItem = document.getElementById("gradeItem");
let orgSelectTag = document.getElementById("ebuOrganization");
let eventSelectTag = document.getElementById("ebuEvent");
let responseContainer = document.getElementById("responseContainer");
let sessionId = document.getElementById("session_id").value;
const rowsPerPage = 10;
let currentPage = 1;
let allSections = [];

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
      sectionId: document.getElementById("sessionIdToBedeleted").value,
      session_id: sessionId
    };
    $.post('src/toolInteract.php', requestData, function(response){
      $('#deleteConfirmModal').modal('hide');
      //$(rowToBeDeleted).remove();
      setupTablePagination();
    }
    ).fail(function(xhr, status, error) {
      console.error('Error submitting delete form:', error);
    });
  });

  //Load BU Organizations
  $.post('src/toolInteract.php', {session_id: sessionId}, function (data) {
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
    console.error('Load BU Experience Organizations failed:', status, error);
  });

    //Setup table and pagination
    setupTablePagination();
    
});

  //Load BU events for given organization
$('#ebuOrganization').on('select2:select', function (e) {
  eventSelectTag.innerHTML = '<option></option>';
  const selectedValue = e.params.data.id;
  const selectedText = e.params.data.text;
  const selectId = $(this).attr('id'); // Get the ID of the changed select
  var requestData = {
    organizationId: selectedValue,
    session_id: sessionId
  };
  $.post('src/toolInteract.php', requestData, function (data) {
    data = JSON.parse(data); 
    data.forEach(function(each){
      const optionElement = document.createElement("option");
      optionElement.value = each.id;
      const dateTime=formatDateTime(each.startDate);
      optionElement.text = each.name+' ('+dateTime.date+'  @ '+dateTime.time+')';
      eventSelectTag.appendChild(optionElement);
    });
  }).fail(function (xhr, status, error) {
    console.error('Load BU Experience Events failed:', status, error);
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
    var requestData = {
      gradeSyncEnabled: true,
      session_id: sessionId
    };
    $.post('src/toolInteract.php', requestData, function (data) {
      data = JSON.parse(data); 
      data.forEach(function(each){
        const optionElement = document.createElement("option");
        optionElement.value = each.id;
        optionElement.text = each.name;
        gradeItem.appendChild(optionElement);
      });
    }).fail(function (xhr, status, error) {
      console.error('Failed to get BLE grade object ids:', status, error);
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
    setupTablePagination();
    responseContainer.className = '';;
    responseContainer.innerHTML = '';
    divHidden.classList.add("hidden");
    gradeItem.removeAttribute('required');
    gradeItem.innerHTML = '<option></option>';
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
  var gradeId = $(closestRow).find('td:eq(5)').text();
  var requestData = {
    updateEvent: true,
    sectionId: sectionId,
    eventId : eventId,
    gradeId : gradeId,
    session_id: sessionId
  };
  $.post('src/toolInteract.php', requestData, function(response){
    img.style.display = 'none';
    $(closestRow).find('span:eq(0)').html('Last updated on <br>'+response);
  }
  ).fail(function(xhr, status, error) {
    img.style.display = 'none';
    console.error('Error submitting form:', error);
  });

}

//request sections data and call print a table and pagination
function setupTablePagination(){
  var requestData = {
    tablePrint: 1,
    session_id: sessionId
  };
  $.post('src/toolInteract.php', requestData, function (data) {
    allSections = JSON.parse(data);
    //allSections = data;
    printTable(currentPage);
    setupPagination(document.getElementById('pagination'));
  }).fail(function (xhr, status, error) {
    console.error('Failed to get data to print to table:', status, error);
  });
}

//printing events to table
function printTable(page) {
  let tableRows = '';
  var tableBody = document.getElementById("eventsList");
  const start = (page - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const paginatedData = allSections.slice(start, end);

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
                            <span style='font-size:14px; grid-column: span 2; grid-row:1;'>Last updated on <br>${event.lastSync}</span>
                            <img src='img/loading.gif' alt='Loading...' class='loading-gif' style='display: none;'>
                            <div class='button-container'>
                                <button type='button' class='btn btn-secondary btn-sm update-btn' onclick='updateEventById(this)'>Update</button>
                                <button type='button' class='btn btn-red btn-sm delete-btn' data-bs-toggle='modal' data-bs-target='#deleteConfirmModal' onclick='setSessionId(this)'>Delete</button>
                            </div>
                        </div>
                    </td>
                  </tr>`;
  });
  tableBody.innerHTML = tableRows;
}

function setupPagination(wrapper){
  wrapper.innerHTML = '';
  const maxVisibleButtons = 3;
  const pageCount = Math.ceil(allSections.length/ rowsPerPage);
  let startPage = Math.max(1, currentPage - Math.floor(maxVisibleButtons / 2));
  let endPage = Math.min(pageCount, startPage + maxVisibleButtons - 1);
  if (endPage - startPage < maxVisibleButtons - 1) {
    startPage = Math.max(1, endPage - maxVisibleButtons + 1);
  }

  const prevButton = createPaginationButton('&laquo;', currentPage > 1 ? currentPage - 1 : 1, currentPage === 1 ? 'disabled' : '');
  wrapper.appendChild(prevButton);
  
  if (startPage > 1) {
    const firstButton = createPaginationButton('1', 1, '');
    wrapper.appendChild(firstButton);

    if (startPage > 2) {
        const dots = document.createElement('li');
        dots.className = 'page-item disabled';
        dots.innerHTML = '<a class="page-link ble-color" href="#">...</a>';
        wrapper.appendChild(dots);
    }
  }

  for (let i = startPage; i <= endPage; i++) {
      const btn = createPaginationButton(i, i, i === currentPage ? 'active' : '');
      wrapper.appendChild(btn);
  }

  if (endPage < pageCount) {
      if (endPage < pageCount - 1) {
          const dots = document.createElement('li');
          dots.className = 'page-item disabled';
          dots.innerHTML = '<a class="page-link ble-color" href="#">...</a>';
          wrapper.appendChild(dots);
      }

      const lastButton = createPaginationButton(pageCount, pageCount, '');
      wrapper.appendChild(lastButton);
  }

  const nextButton = createPaginationButton('&raquo;', currentPage < pageCount ? currentPage + 1 : pageCount, currentPage === pageCount ? 'disabled' : '');
  wrapper.appendChild(nextButton);
}


function createPaginationButton(text, page, className) {
  const li = document.createElement('li');
  li.className = `page-item ${className}`;
  const a = document.createElement('a');
  a.className = 'page-link ble-color';
  a.href = '#';
  a.innerHTML = text;
  a.addEventListener('click', (e) => {
      e.preventDefault();
      currentPage = page;
      printTable(currentPage);
      setupPagination(document.getElementById('pagination'));
  });
  li.appendChild(a);
  return li;
}