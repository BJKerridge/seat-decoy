@extends('web::layouts.grids.12')

@section('title', 'HONKERSBONKERS Dashboard')
@section('page_header', '')
@section('page_description', 'HONKERSBONKERS Dashboard')

@section('content')
<style>
.table td, .table th{
    padding: 0.5rem;
}
</style>
<!------------ TABLE OF FLEETS ---------------->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body" style="line-height: 12px; font-size: 12px;">
                <h4 class="text-center">Fleets</h4>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="importance-col" style="width: 16px;"></th>
                            <th class="time-col" style="width: 120px;">Fleet Time</th>
                            <th class="countdown-col" style="width: 120px;">Time Until</th>
                            <th class="name-col" style="width: 100px;">Fleet Name</th>
                            <th class="comp-col" style="width: 150px;">Composition</th>
                            <th class="location-col" style="width: 80px;">Form-up</th>
                            <th class="message-col">Message</th>
                            <th class="view-col" style="width: 32px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($fleets as $fleet)
    <tr data-fleet-time="{{ \Carbon\Carbon::parse($fleet->fleet_time)->timestamp }}" data-fleet-id="{{ $fleet->id }}">
        <td class="importance-col">{{ $fleet->importance }}</td>
        <td class="time-col">{{ \Carbon\Carbon::parse($fleet->fleet_time)->format('Y-m-d H:i') }}</td>
        <td class="countdown-col"></td>
        <td class="name-col">{{ $fleet->fleet_name }}</td>
        <td class="comp-col">{{ $fleet->fleet_comp }}</td>
        <td class="location-col">{{ $fleet->formup_location }}</td>
        <td class="message-col">{{ $fleet->message }}</td>
        <td>
            <button class="btn btn-info btn-sm" 
                data-toggle="modal" 
                data-target="#fleetViewerModal"
                data-fleet-id="{{ $fleet->id }}"
                data-fleet-time="{{ \Carbon\Carbon::parse($fleet->fleet_time)->format('Y-m-d H:i') }}"
                data-fleet-name="{{ $fleet->fleet_name }}"
                data-fleet-comp="{{ $fleet->fleet_comp }}"
                data-formup-location="{{ $fleet->formup_location }}"
                data-importance="{{ $fleet->importance }}"
                data-message="{{ $fleet->message }}"
                data-fleet-members="{{ $fleet->fleet_members }}">
                <span class="far fa-eye"></span>
            </button>
        </td>
        <td>
            <form action="{{ route('decoy::fleetDestroy', $fleet->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <span class="far fa-trash-alt"></span>
                </button>
            </form>
        </td>
    </tr>
@endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!------------ FOOTER BUTTONS ---------------->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createFleetModal"><i class="far fa-calendar-plus"></i> New Fleet</button>


<!------------ SCRIPT FOR COUNTDOWN ---------------->
<script>
    function updateCountdowns() {
        document.querySelectorAll('tr[data-fleet-time]').forEach(row => {
            const fleetTime = parseInt(row.getAttribute('data-fleet-time')) * 1000; // Convert to milliseconds
            const countdownCell = row.querySelector('.countdown-col');
            const now = new Date().getTime();
            const diff = fleetTime - now;

            if (diff > 0) {
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff / (1000 * 60 * 60)) - days * 24);
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                countdownCell.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            } else {
                countdownCell.textContent = "Departed";
            }
        });
    }
    setInterval(updateCountdowns, 1000);
    updateCountdowns();
</script>

<!------------ SCRIPT FOR FLEET VIEWER MODAL ---------------->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var buttons = document.querySelectorAll('.btn-info');

    buttons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            var fleetId = button.getAttribute('data-fleet-id');
            var fleetTime = button.getAttribute('data-fleet-time');
            var fleetName = button.getAttribute('data-fleet-name');
            var fleetComp = button.getAttribute('data-fleet-comp');
            var formupLocation = button.getAttribute('data-formup-location');
            var importance = button.getAttribute('data-importance');
            var message = button.getAttribute('data-message');
            var fleet_members = button.getAttribute('data-fleet-members');

            document.getElementById('fleet_id').value = fleetId;
            document.getElementById('fleet_name').value = fleetName;
            document.getElementById('fleet_time').value = fleetTime;
            document.getElementById('fleet_comp').value = fleetComp;
            document.getElementById('formup_location').value = formupLocation;
            document.getElementById('importance').value = importance;
            document.getElementById('message').value = message;
            document.getElementById('fleet_members').value = fleet_members;

            document.getElementById('fleet_id').setAttribute('readonly', true);
            document.getElementById('fleet_name').setAttribute('readonly', true);
            document.getElementById('fleet_time').setAttribute('readonly', true);
            document.getElementById('fleet_comp').setAttribute('readonly', true);
            document.getElementById('formup_location').setAttribute('readonly', true);
            document.getElementById('importance').setAttribute('readonly', true);
            document.getElementById('message').setAttribute('readonly', true);
            document.getElementById('fleet_members').setAttribute('readonly', true);

            // **Button Visibility Logic**
            document.getElementById('editFleetButton').style.display = 'inline-block';
            document.getElementById('saveFleetButton').style.display = 'none';
            document.getElementById('saveFleetMembers').style.display = 'none';

            // **Check if the fleet_members value is valid JSON**
            try {
                const parsedFleetMembers = JSON.parse(fleet_members);
                
                // Check if it's a valid array and has content
                if (Array.isArray(parsedFleetMembers) && parsedFleetMembers.length > 0) {
                    // Valid JSON, proceed as normal (show the table)
                    document.getElementById('trackFleetButton').style.display = 'none';
                    document.querySelector('.form-group table').style.display = '';
                    document.querySelector('#fleet_members').style.display = 'none';
                    document.getElementById('editFleetMembers').style.display = 'inline-block'; // Show 'Edit Fleet Members' button
                } else {
                    // Invalid JSON (empty or malformed array)
                    throw new Error("Invalid fleet members data");
                }
            } catch (e) {
                // Invalid or malformed JSON
                document.getElementById('trackFleetButton').style.display = 'inline-block';
                document.querySelector('.form-group table').style.display = 'none';
                document.querySelector('#fleet_members').style.display = 'inline-block';
                document.getElementById('fleet_members').removeAttribute('readonly');

                // Hide 'Edit Fleet Members' button if there's no valid table
                document.getElementById('editFleetMembers').style.display = 'none';
            }

            // Show images, hide remove buttons
            document.querySelectorAll('.fleet-member-image').forEach(img => img.style.display = 'inline-block');
            document.querySelectorAll('.fleet-member-remove').forEach(btn => btn.style.display = 'none');
        });
    });

    // **Track Fleet Button Logic** (Submit action)
    document.getElementById('trackFleetButton').addEventListener('click', function () {
        // We don't need to validate here, backend will handle the data
        var fleetMembers = document.getElementById('fleet_members').value;

        // Proceed with form submission or data processing here
        console.log("Fleet Members to be sent:", fleetMembers);

        // Example form submission:
        document.getElementById('fleetForm').submit(); // Or send via AJAX, etc.
    });

    // **Edit Fleet Members Logic**
    document.getElementById('editFleetMembers').addEventListener('click', function () {
        document.getElementById('editFleetMembers').style.display = 'none';
        document.getElementById('saveFleetMembers').style.display = 'inline-block';

        var addRowButton = document.getElementById('addRowButton');
        if (addRowButton) {
            addRowButton.style.display = 'inline-block'; // Show "Add Row" button when editing members
        }

        // Hide images, show remove buttons
        document.querySelectorAll('.fleet-member-image').forEach(img => img.style.display = 'none');
        document.querySelectorAll('.fleet-member-remove').forEach(btn => btn.style.display = 'inline-block');
    });

    // **Save Fleet Members Logic**
    document.getElementById('saveFleetMembers').addEventListener('click', function () {
        document.getElementById('editFleetMembers').style.display = 'inline-block';
        document.getElementById('saveFleetMembers').style.display = 'none';

        var addRowButton = document.getElementById('addRowButton');
        if (addRowButton) {
            addRowButton.style.display = 'none'; // Hide "Add Row" button when saving
        }

        // Show images, hide remove buttons again
        document.querySelectorAll('.fleet-member-image').forEach(img => img.style.display = 'inline-block');
        document.querySelectorAll('.fleet-member-remove').forEach(btn => btn.style.display = 'none');
    });
});
</script>


<div class="modal fade" id="fleetViewerModal" tabindex="-1" role="dialog" aria-labelledby="fleetViewerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFleetModalLabel">View Fleet Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="fleetUpdateForm" action="{{ route('decoy::updateFleet') }}" method="POST">
                    @csrf
                    <input type="hidden" id="fleet_id" name="fleet_id" value="">

                    <div class="row">
                        <div class="col-md-9 form-group">
                            <label for="fleet_time">Fleet Time</label>
                            <input type="datetime-local" id="fleet_time" name="fleet_time" class="form-control" readonly>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="duration">Duration</label>
                            <input type="text" id="duration" name="duration" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="fleet_name">Fleet Name</label>
                            <input type="text" id="fleet_name" name="fleet_name" class="form-control" readonly>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="formup_location">Formup Location</label>
                            <input type="text" id="formup_location" name="formup_location" class="form-control" readonly>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="importance">Importance</label>
                            <select id="importance" name="importance" class="form-control" readonly>
                                <option value="1">1 (CTA)</option>
                                <option value="2">2 (Strat)</option>
                                <option value="3">3 (Skirmish)</option>
                                <option value="4">4 (Home Def)</option>
                                <option value="5">5 (Roam)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="fleet_comp">Fleet Composition</label>
                        <textarea id="fleet_comp" name="fleet_comp" class="form-control" readonly></textarea>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-control" readonly></textarea>
                    </div>
                    <button type="button" class="btn btn-warning float-right" id="editFleetButton"><i class="fas fa-pencil-alt"></i> Edit Fleet Details</button>
                    <button type="submit" class="btn btn-success float-right" id="saveFleetButton"><i class="fas fa-check"></i> Save Fleet Details</button>
                    <br />
                    <br />
                    <div class="form-group">
                        <label for="fleet_members">Fleet Members</label>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead><tr>
                                    <th style="width: 80px;"></th>
                                    <th>User</th>
                                    <th>Ship</th>
                                </tr></thead>
                                <tbody id="fleet-members-table">
                                </tbody>
                            </table>
                        </div>
                        <textarea id="fleet_members" name="fleet_members" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success float-right" id="trackFleetButton"><i class="far fa-eye"></i> Track Fleet</button>
                    <button type="button" class="btn btn-warning float-right" id="editFleetMembers"><i class="fas fa-pencil-alt"></i> Edit Fleet Members</button>
                    <button type="submit" class="btn btn-success float-right" id="saveFleetMembers"><i class="fas fa-check"></i> Save Fleet Members</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Get the edit and save buttons
    var editFleetButton = document.getElementById('editFleetButton');
    var saveFleetButton = document.getElementById('saveFleetButton');

    // Get all form-control fields
    var formControls = document.querySelectorAll('.form-control');

    // Add click event to the edit button
    editFleetButton.addEventListener('click', function () {
        // Make all form-control fields editable
        formControls.forEach(function (field) {
            field.removeAttribute('readonly');
        });

        // Hide the edit button and show the save button
        editFleetButton.style.display = 'none';
        saveFleetButton.style.display = 'inline-block';
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editFleetMembersButton = document.getElementById('editFleetMembers');
    var saveFleetMembersButton = document.getElementById('saveFleetMembers');
    var fleetMembersTable = document.getElementById('fleet-members-table');
    var fleetUpdateForm = document.getElementById('fleetUpdateForm');

    editFleetMembersButton.addEventListener('click', function () {
        editFleetMembersButton.style.display = 'none';
        saveFleetMembersButton.style.display = 'inline-block';

        // Ensure only one add row button exists
        if (!document.getElementById('addRowButton')) {
            var addRowButton = document.createElement('button');
            addRowButton.id = 'addRowButton';
            addRowButton.className = 'btn btn-primary btn-sm add-row';
            addRowButton.type = 'button'; // Prevent form submission
            addRowButton.style = 'padding: 3px 7px; margin: 5px 0;';
            addRowButton.innerHTML = '<i class="fas fa-plus"></i> Add Pilot';

            // Create a new row for the Add Row button in the 3rd column of the table
            var newRow = document.createElement('tr');
            var cell = document.createElement('td');
            cell.colSpan = 3; // Make the button span all three columns
            cell.style.textAlign = 'right'; // Align the button to the right
            cell.appendChild(addRowButton);

            newRow.appendChild(cell);
            fleetMembersTable.appendChild(newRow); // Append the row with the button
        }

        fleetMembersTable.querySelectorAll('tr').forEach(function (row) {
            var cells = row.getElementsByTagName('td');

            if (cells.length >= 3) {
                var actionCell = cells[0];
                actionCell.innerHTML = ` 
                    <button type="button" class="btn btn-danger btn-sm remove-row" style="padding: 3px 7px; margin: 0;">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                // Make the 'ship name' column always editable
                cells[2].setAttribute('contenteditable', 'true');
            }
        });

        // Add event listener for removing a row
        fleetMembersTable.addEventListener('click', function (event) {
            if (event.target.closest('.remove-row')) {
                event.target.closest('tr').remove();
            }
        });

        // Add event listener for adding a new row
        document.getElementById('addRowButton').addEventListener('click', function (event) {
            event.preventDefault(); // Stop form submission
            var newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row" style="padding: 3px 7px; margin: 0;">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
                <td contenteditable="true">New User</td>
                <td contenteditable="true">New Ship</td> <!-- Ship name always editable -->
            `;
            fleetMembersTable.insertBefore(newRow, fleetMembersTable.lastElementChild); // Add the new row before the "add row" button
        });
    });

    // Handle form submission when 'Save Fleet Members' is clicked
    saveFleetMembersButton.addEventListener('click', function (event) {
        // Prevent default form submission to gather the data first
        event.preventDefault();

        // Collect data from the table
        var fleetMembers = [];
        var rows = fleetMembersTable.querySelectorAll('tr');
        
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            if (cells.length === 3) { // Ignore rows that don't have all 3 columns
                var userName = cells[1].textContent.trim(); // Get user name
                var shipName = cells[2].textContent.trim(); // Get ship name

                if (userName && shipName) {
                    fleetMembers.push({
                        user_name: userName,
                        ship_name: shipName
                    });
                }
            }
        });

        // Add the fleet members data to a hidden textarea or input (or any other suitable way)
        var fleetMembersInput = document.getElementById('fleet_members');
        fleetMembersInput.value = JSON.stringify(fleetMembers);

        // Now, submit the form with the added fleet members data
        fleetUpdateForm.submit();
    });
});
</script>











<!-- SCRIPT TO CREATE THE USER TABLE -->
<script>
    // Add event listener for all buttons that open the modal
    document.querySelectorAll('.btn-info').forEach(function(button) {
        button.addEventListener('click', function() {
            var fleetMembersData = button.getAttribute('data-fleet-members');
            var fleetMembers = JSON.parse(fleetMembersData);  // Parse the JSON string into an object
            var tableBody = document.querySelector('#fleetViewerModal .table tbody');
            tableBody.innerHTML = '';
            fleetMembers.forEach(function(member, index) {
                var row = document.createElement('tr');
                var userIdCell = document.createElement('td');
                var userImage = document.createElement('img');
                userImage.src = "//images.evetech.net/characters/" + member.character_id + "/portrait?size=32"; // Set the image source dynamically using the character_id
                userImage.title = member.name; // Set the title attribute to the member's name
                userImage.classList.add('img-circle');
                userImage.classList.add('fleet-member-image');
                userIdCell.appendChild(userImage);
                row.appendChild(userIdCell);
                var userNameCell = document.createElement('td');
                userNameCell.textContent = member.name;
                row.appendChild(userNameCell);
                var shipCell = document.createElement('td');
                shipCell.textContent = member.ship || 'No Ship Info';  // Ship ID or a fallback message
                row.appendChild(shipCell);

                tableBody.appendChild(row);
            });
        });
    });
</script>





<!------------ MODAL: Fleet Adder ---------------->
<div class="modal fade" id="createFleetModal" tabindex="-1" role="dialog" aria-labelledby="createFleetModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header">
        <h5 class="modal-title" id="createFleetModalLabel">Create New Fleet</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
        <form action="{{ route('decoy::storeFleet') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-9 form-group"><label for="fleet_time">Fleet Time</label>
                    <input type="datetime-local" id="fleet_time" name="fleet_time" class="form-control" required>
                </div>
                <div class="col-md-3 form-group"><label for="duration">Duration</label>
                    <input type="text" id="duration" name="duration" class="form-control" required></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group"><label for="fleet_name">Fleet Name</label>
                    <input type="text" id="fleet_name" name="fleet_name" class="form-control" required>
                </div>
                <div class="col-md-4 form-group"><label for="formup_location">Formup Location</label>
                    <input type="text" id="formup_location" name="formup_location" class="form-control" required>
                </div>
                <div class="col-md-4 form-group"><label for="importance">Importance</label>
                    <select id="importance" name="importance" class="form-control" required>
                        <option value="1">1 (CTA)</option>
                        <option value="2">2 (Strat)</option>
                        <option value="3">3 (Skirmish)</option>
                        <option value="4">4 (Home Def)</option>
                        <option value="5">5 (Roam)</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label for="fleet_comp">Fleet Composition</label>
                <textarea id="fleet_comp" name="fleet_comp" class="form-control" required></textarea>
            </div>
            <div class="form-group"><label for="message">Message</label>
                <textarea id="message" name="message" class="form-control" required></textarea>
            </div>
            <button type="submit" class="btn btn-success">Submit</button>
        </form>
    </div></div></div>
</div>
@endsection