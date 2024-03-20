let ebuGradeSyncCheck = document.getElementById("ebuGradeSync");
let divHidden = document.getElementById("ebuGradeSyncDiv");
let gradeItem = document.getElementById("gradeItem");
let orgSelectTag = document.getElementById("ebuOrganization");
let eventSelectTag = document.getElementById("ebuEvent");

$(document).ready(function() {
  $('.select2').select2({
    placeholder: 'Select an option', // Placeholder text
    width: '100%', // Adjust the width as needed
  });
  
  // Form submit: Creates a new section and enrolls engage users to current org unit
  $('#ebuForm').submit(function(event) {
    event.preventDefault();
    var formData = $(this).serialize();
    // Make the POST request
    $.ajax({
      type: 'POST',
      url: 'src/toolInteract.php',
      data: formData,
      success: function(response) {
        console.log('Form submitted successfully:', response);
      },
      error: function(xhr, status, error) {
        console.error('Error submitting form:', error);
      }
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
      optionElement.text = each.name;
      eventSelectTag.appendChild(optionElement);
    });
  }).fail(function (xhr, status, error) {
    console.error('GET request failed:', status, error);
  });
});



// Add an event listener to the checkbox
// ebuGradeSyncCheck.addEventListener("change", function () {
//   if (ebuGradeSyncCheck.checked) {
//     gradeItem.innerHTML = '<option></option>';
//     $.get('src/toolInteract.php', function (data) {
//       data = JSON.parse(data); 
//       data.forEach(function(each){
//         const optionElement = document.createElement("option");
//         optionElement.value = each.id;
//         optionElement.text = each.name;
//         gradeItem.appendChild(optionElement);
//       });
//     }).fail(function (xhr, status, error) {
//       console.error('GET request failed:', status, error);
//     });
//     divHidden.classList.remove("hidden");
//     gradeItem.setAttribute('required', '');
//   } else {
//     divHidden.classList.add("hidden");
//     gradeItem.removeAttribute('required');
//     gradeItem.innerHTML = '<option></option>';
//   }
// });
