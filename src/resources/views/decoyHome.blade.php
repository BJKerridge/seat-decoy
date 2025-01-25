@extends('web::layouts.grids.12')
@section('title', 'Pilot Dashboard')
@section('page_header', '')
@section('page_description', 'Pilot Dashboard')

@section('content')
<style>
.table td, .table th{
    padding: 0.3rem;
}

.align-middle-row td {
    vertical-align: middle;
}

.align-middle-row th {
    vertical-align: middle;
}

</style>
@php
    $decoy = json_decode($decoyPilots, true); // Decode as an associative array
    $nonDecoy = json_decode($nonDecoyPilots, true); // Decode as an associative array
@endphp
<div class="row">

    <!-- Your Kills -->
    <div class="col-md-4 col-sm-6">
      <div class="info-box">
      <span class="info-box-icon elevation-1"><img src ='https://images.evetech.net/characters/{{ auth()->user()->main_character_id }}/portrait?size=64'></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Your Kills (all pilots)</span>
          <span class="info-box-number">{{ $mainInfo['kills'] }}</span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>

    <!-- Linked Characters -->
    <div class="col-md-4 col-sm-6">
      <div class="info-box">
        <span class="info-box-icon bg-yellow elevation-1"><i class="fa fa-key"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Linked Characters</span>
          <span class="info-box-number">{{ count(auth()->user()->associatedCharacterIds()) }}</span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>
    
    <!-- Total Character Isk -->
    <div class="col-md-4 col-sm-6">
      <div class="info-box">
        <span class="info-box-icon bg-green elevation-1"><i class="far fa-money-bill-alt"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Total Character Isk</span>
          <span class="info-box-number">{{ number_format($totalIsk, 0, '.', ',') }}</span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>

</div>

<div class="row">

    <!-- Decoy Kills -->
    <div class="col-md-4 col-sm-6">
      <div class="info-box">
      <span class="info-box-icon elevation-1"><img src ='https://images.evetech.net/alliances/99012410/logo?size=64'></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Decoy Kills</span>
          <span class="info-box-number">{{ $decoyKills }}</span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>

        <!-- Last Fleet Time -->
        <div class="col-md-4 col-sm-6">
      <div class="info-box">
        <span class="info-box-icon bg-aqua elevation-1"><i class="fa fa-calendar"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Last Fleet Time</span>
          <span class="info-box-number">
          {{ $lastFleetTime === 'N/A' ? 'N/A' : (\Carbon\Carbon::parse(trim($lastFleetTime, '"'))->format('jS F Y, h:ia')) }}
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>

    <!-- Total Fleets -->
    <div class="col-md-4 col-sm-6">
      <div class="info-box">
      <span class="info-box-icon bg-red elevation-1"><i class="fas fa-space-shuttle"></i></span>
          <div class="info-box-content">
          <span class="info-box-text">Total Fleets / Honkers Bonkers</span>
          <span class="info-box-number">{{ $mainInfo['uniqueFleets'] }} / {{ $mainInfo['totalFleets'] }}</span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>

</div>

@php
    // Decode the JSON array
    $filterArray = json_decode($filter, true);

    // Ensure $filterArray is a valid array and extract the first element as a string
    if (is_array($filterArray) && count($filterArray) > 0 && is_string($filterArray[0])) {
        $filterString = $filterArray[0];
    } else {
        $filterString = "111111111111"; // Default if invalid
    }

    // Ensure $filterString is exactly 12 characters long, otherwise reset to default
    if (strlen($filterString) !== 12 || preg_match('/[^01]/', $filterString)) {
        $filterString = "111111111111";
    }
@endphp

<script>
  const csrfToken = '{{ csrf_token() }}';

  document.addEventListener('DOMContentLoaded', function () {
    // PHP-generated filter string
    const filterString = '{{ $filterString }}'; // Inject PHP variable into JavaScript

    // Get all checkboxes
    const checkboxes = document.querySelectorAll('.column-toggle');

    checkboxes.forEach(function (checkbox, index) {
      // Set the initial checkbox state based on filterString
      const isChecked = filterString[index] === '1';
      checkbox.checked = isChecked;

      // Get the column index from the checkbox's data attribute
      const columnIndex = checkbox.getAttribute('data-column');

      // Set the visibility of the corresponding table column
      const columnCells = document.querySelectorAll('.column-' + columnIndex);
      columnCells.forEach(function (cell) {
        cell.style.display = isChecked ? '' : 'none';
      });

      // Add event listener for changes
      checkbox.addEventListener('change', function () {
        const isChecked = this.checked;
        columnCells.forEach(function (cell) {
          cell.style.display = isChecked ? '' : 'none';
        });
      });
    });
  });
</script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const filterString = '{{ $filterString }}';
    const checkboxes = document.querySelectorAll('.column-toggle');
    const filterInput = document.getElementById('filterInput');

    // Initialize checkboxes and table columns
    checkboxes.forEach((checkbox, index) => {
      const isChecked = filterString[index] === '1';
      checkbox.checked = isChecked;

      const columnIndex = checkbox.getAttribute('data-column');
      document.querySelectorAll('.column-' + columnIndex).forEach(cell => {
        cell.style.display = isChecked ? '' : 'none';
      });

      // Update column visibility on checkbox change
      checkbox.addEventListener('change', function () {
        document.querySelectorAll('.column-' + columnIndex).forEach(cell => {
          cell.style.display = this.checked ? '' : 'none';
        });
      });
    });

    // Update filter input before form submission
    document.getElementById('filterForm').addEventListener('submit', function () {
      let newFilter = '';
      checkboxes.forEach(checkbox => {
        newFilter += checkbox.checked ? '1' : '0';
      });
      filterInput.value = newFilter;
    });
  });
</script>

<div class="row">
  <div class="col-sm-6 d-flex flex-column flex-sm-row">
      <button class="btn btn-primary mb-2 mb-sm-0 mr-sm-2" data-toggle="modal" data-target="#orderModalDecoy">Change DECOY Pilot Order</button>
      <button class="btn btn-primary mb-2 mb-sm-0 mr-sm-2" data-toggle="modal" data-target="#orderModalNonDecoy">Change Non-DECOY Pilot Order</button>
      <button class="btn btn-primary mb-2 mb-sm-0" data-toggle="modal" data-target="#filterModal">Toggle Columns</button>
  </div>
</div>

<div class="row">
  <div class="card-body table-responsive" style="line-height: 12px; font-size: 12px;">
    <table class="table table-hover table-striped">
      <thead class="thead-light">
        <tr class="align-middle-row">
          <th style="width: 32px;"></th>
            <th class="column-1">Pilot</th>
            <th class="column-2">Sec</th>
            <th class="column-3">Home</th>
            <th class="column-4">Skills</th>
            <th class="column-5">Standings</th>
            <th class="column-6">PvP</th>
            <th class="column-7">Total Isk</th>
            <th class="column-9">Isk Income (30d)</th>
            <th class="column-11">Mining</th>
            <th class="column-12">Industry Slots</th>
            <th class="column-13" width="220px">Planetary Interaction</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($decoy as $pilot)
          <tr class="align-middle-row">
            <td><img src="https://images.evetech.net/characters/{{ $pilot['character_id'] }}/portrait?size=32" class="img-circle elevation-2" /></td>
            <td class="column-1">{{ $pilot['name'] }}</td>
            <td class="column-2">{{ $pilot['sec'] }}</td>
            <td class="column-3">{{ $pilot['home'] }}</td>
            <td class="column-4">
              @if(empty($pilot['training_until']))
              No skill in training
              @else
              {{ $pilot['training_until'] }}<br /><br />
              @php
                $skills = json_decode($pilot['training_skills'], true) ?? []; // Ensure it's an array
                $tooltipText = implode('
                ', $skills); // Use newline character for multi-line tooltip
              @endphp
              <span title="{{ $tooltipText }}">
                {{ $skills[0] ?? 'No skill in training' }}
              </span>
              @endif
            </td>
            <td class="column-5">{{ $pilot['standings_angel'] }} - Angels
              <br />{{ $pilot['standings_trig'] }} - Trigs
              <br />{{ $pilot['standings_eden'] }} - Eden</td>
            <td class="column-6">{{ $pilot['fleets'] }} Fleets (Rank #69)
              <br />{{ $pilot['killmails'] }} Killmails (Rank #4)
              <br />{{ number_format($pilot['kill_value'], 2) }} ISK Destroyed</td>
            <td class="column-7">{{ number_format($pilot['isk_total'], 2) }} ISK<span class="column-8">
              <br />{{ number_format($pilot['isk_market'], 2) }} Sell Orders</span></td>
            <td class="column-9"><b>{{ number_format($pilot['isk_ratting'], 2) }}</b> ISK <span class="column-10"> Ratting
              <br /><b>{{ number_format($pilot['isk_incursions'], 2) }}</b> ISK Incursions
              <br /><b>{{ number_format($pilot['isk_missions'], 2) }}</b> ISK Missions</span></td>
            <td class="column-11">{{ number_format($pilot['mining_value'], 2) }} ISK
              <br />{{ number_format($pilot['mining_m3'], 2) }} m3</td>
            <td class="column-12">{{ $pilot['industry_manufacturing_slots'] }}/{{ $pilot['industry_manufacturing_slots_total'] }} Man.
              <br />{{ $pilot['industry_research_slots'] }}/{{ $pilot['industry_research_slots_total'] }} Res.
              <br />{{ $pilot['industry_reaction_slots'] }}/{{ $pilot['industry_reaction_slots_total'] }} React.</td>
            <td class="column-13">
              @php
                $planets = json_decode($pilot['planets'], true);
                $planetsCount = count($planets);
                $missingPlanets = 6 - $planetsCount;
              @endphp
              @foreach($planets as $planet)
              @php
                  $extractorEnd = $planet['extractor_end'] ? \Carbon\Carbon::parse($planet['extractor_end']) : null;
                  $hasPassed = $extractorEnd ? $extractorEnd->isPast() : false;
                  $hoursRemaining = $extractorEnd ? $extractorEnd->diffInHours(\Carbon\Carbon::now()) : 0;
              @endphp
              <img src="https://images.evetech.net/types/{{ $planet['image'] }}/icon?size=32" 
     class="img-circle elevation-2" 
     title="{{ $planet['planet_name'] }}&#10;@if (!$extractorEnd || $hasPassed) Requires attention @endif" 
     @if (!$extractorEnd || $hasPassed) style="filter: grayscale(100%);" @endif />
              @endforeach
              @for($i = 0; $i < $missingPlanets; $i++)
              <img src="https://images.evetech.net/types/25236/icon?size=32" class="img-circle elevation-2" title="N/A" style="filter: grayscale(100%);" />
              @endfor
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="row" {{ !empty($nonDecoy) ? 'extraTable' : 'hidden' }}>
  <div class="card-body table-responsive" style="line-height: 12px; font-size: 12px;">
    <table class="table table-hover table-striped">
      <thead class="thead-light">
        <tr class="align-middle-row">
          <th style="width: 32px;"></th>
            <th class="column-1">Pilot</th>
            <th class="column-2">Sec</th>
            <th class="column-3">Home</th>
            <th class="column-4">Skills</th>
            <th class="column-5">Standings</th>
            <th class="column-6">PvP</th>
            <th class="column-7">Total Isk</th>
            <th class="column-9">Isk Income (30d)</th>
            <th class="column-11">Mining</th>
            <th class="column-12">Industry Slots</th>
            <th class="column-13" width="220px">Planetary Interaction</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($nonDecoy as $pilot)
          <tr class="align-middle-row">
            <td><img src="https://images.evetech.net/characters/{{ $pilot['character_id'] }}/portrait?size=32" class="img-circle elevation-2" /></td>
            <td class="column-1">{{ $pilot['name'] }}</td>
            <td class="column-2">{{ $pilot['sec'] }}</td>
            <td class="column-3">{{ $pilot['home'] }}</td>
            <td class="column-4">
              @if(empty($pilot['training_until']))
              No skill in training
              @else
              {{ $pilot['training_until'] }}<br /><br />
              @php
                $skills = json_decode($pilot['training_skills'], true) ?? []; // Ensure it's an array
                $tooltipText = implode('
                ', $skills); // Use newline character for multi-line tooltip
              @endphp
              <span title="{{ $tooltipText }}">
                {{ $skills[0] ?? 'No skill in training' }}
              </span>
              @endif
            </td>
            <td class="column-5">{{ $pilot['standings_angel'] }} - Angels
              <br />{{ $pilot['standings_trig'] }} - Trigs
              <br />{{ $pilot['standings_eden'] }} - Eden</td>
            <td class="column-6">{{ $pilot['fleets'] }} Fleets (Rank #69)
              <br />{{ $pilot['killmails'] }} Killmails (Rank #4)
              <br />{{ number_format($pilot['kill_value'], 2) }} ISK Destroyed</td>
            <td class="column-7">{{ number_format($pilot['isk_total'], 2) }} ISK<span class="column-8">
              <br />{{ number_format($pilot['isk_market'], 2) }} Sell Orders</span></td>
            <td class="column-9"><b>{{ number_format($pilot['isk_ratting'], 2) }}</b> ISK <span class="column-10"> Ratting
              <br /><b>{{ number_format($pilot['isk_incursions'], 2) }}</b> ISK Incursions
              <br /><b>{{ number_format($pilot['isk_missions'], 2) }}</b> ISK Missions</span></td>
            <td class="column-11">{{ number_format($pilot['mining_value'], 2) }} ISK
              <br />{{ number_format($pilot['mining_m3'], 2) }} m3</td>
            <td class="column-12">{{ $pilot['industry_manufacturing_slots'] }}/{{ $pilot['industry_manufacturing_slots_total'] }} Man.
              <br />{{ $pilot['industry_research_slots'] }}/{{ $pilot['industry_research_slots_total'] }} Res.
              <br />{{ $pilot['industry_reaction_slots'] }}/{{ $pilot['industry_reaction_slots_total'] }} React.</td>
            <td class="column-13">
            @php
                $planets = json_decode($pilot['planets'], true);
                $planetsCount = count($planets);
                $missingPlanets = 6 - $planetsCount;
              @endphp
              @foreach($planets as $planet)
              @php
                  $extractorEnd = $planet['extractor_end'] ? \Carbon\Carbon::parse($planet['extractor_end']) : null;
                  $hasPassed = $extractorEnd ? $extractorEnd->isPast() : false;
                  $hoursRemaining = $extractorEnd ? $extractorEnd->diffInHours(\Carbon\Carbon::now()) : 0;
              @endphp
              <img src="https://images.evetech.net/types/{{ $planet['image'] }}/icon?size=32" 
     class="img-circle elevation-2" 
     title="{{ $planet['planet_name'] }}&#10;@if (!$extractorEnd || $hasPassed) Requires attention @endif" 
     @if (!$extractorEnd || $hasPassed) style="filter: grayscale(100%);" @endif />
              @endforeach
              @for($i = 0; $i < $missingPlanets; $i++)
              <img src="https://images.evetech.net/types/25236/icon?size=32" class="img-circle elevation-2" title="N/A" style="filter: grayscale(100%);" />
              @endfor
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="orderModalDecoy" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderModalLabel">Change Pilot Order</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Table inside the modal -->
        <form id="orderForm-orderModalDecoy" action="{{ route('decoy::updateOrder') }}" method="POST">
        @csrf
          <table class="table table-hover table-striped">
            @foreach ($decoy as $pilot)
              <tr>
                <td>
                  <button type="button" class="up-btn" data-pilot="{{ $pilot['name'] }}" data-character_id="{{ $pilot['character_id'] }}">↑</button>
                  <button type="button" class="down-btn" data-pilot="{{ $pilot['name'] }}" data-character_id="{{ $pilot['character_id'] }}">↓</button>
                </td>
                <td>
                  <span>{{ $pilot['name'] }}</span>
                  <!-- Hidden input to store the pilot's character_id -->
                  <input type="hidden" name="order[{{ $pilot['character_id'] }}][name]" value="{{ $pilot['name'] }}" />
                  <input type="hidden" name="order[{{ $pilot['character_id'] }}][character_id]" value="{{ $pilot['character_id'] }}" />
                  <input type="hidden" name="order[{{ $pilot['character_id'] }}][order]" value="{{ $pilot['order'] }}" />
                </td>
              </tr>
            @endforeach
          </table>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveOrder-orderModalDecoy">Save Order</button>
        </div>
    </div>
  </div>
</div>


<script>
// Function to handle up/down button clicks for both modals
function handleButtonClicks(modalId) {
  // Handle up and down button clicks within the given modal
  document.querySelectorAll(`#${modalId} .up-btn`).forEach(function(button) {
    button.addEventListener('click', function() {
      const row = button.closest('tr');
      const prevRow = row.previousElementSibling;
      if (prevRow) {
        row.parentNode.insertBefore(row, prevRow);
        updateOrder(modalId);
      }
    });
  });

  document.querySelectorAll(`#${modalId} .down-btn`).forEach(function(button) {
    button.addEventListener('click', function() {
      const row = button.closest('tr');
      const nextRow = row.nextElementSibling;
      if (nextRow) {
        row.parentNode.insertBefore(nextRow, row);
        updateOrder(modalId);
      }
    });
  });
}

// Function to update the order based on the modal
function updateOrder(modalId) {
  const tableRows = document.querySelectorAll(`#${modalId} table tr`);

  tableRows.forEach(function(row, index) {
    const character_id = row.querySelector('input[name*="[character_id]"]').value;
    const hiddenInputOrder = row.querySelector(`input[name="order[${character_id}][order]"]`);
    hiddenInputOrder.value = index + 1;
  });
}

// Handle save button click for both modals
function handleSaveButtonClick(modalId) {
  document.getElementById(`saveOrder-${modalId}`).addEventListener('click', function() {
    const tableRows = document.querySelectorAll(`#${modalId} table tr`);

    tableRows.forEach(function(row, index) {
      const character_id = row.querySelector('input[name*="[character_id]"]').value;
      const hiddenInputOrder = row.querySelector(`input[name="order[${character_id}][order]"]`);
      hiddenInputOrder.value = index + 1;
      console.log(`Updated Order for character_id ${character_id}: ${index + 1}`);
    });

    document.getElementById(`orderForm-${modalId}`).submit();
  });
}

// Initialize the functionality for both modals
document.addEventListener('DOMContentLoaded', function() {
  // Handle up/down buttons for both modals
  handleButtonClicks('orderModalDecoy');
  handleButtonClicks('orderModalNonDecoy');

  // Handle save button clicks for both modals
  handleSaveButtonClick('orderModalDecoy');
  handleSaveButtonClick('orderModalNonDecoy');
});

</script>




<!-- Modal -->
<div class="modal fade" id="orderModalNonDecoy" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderModalLabel">Change Pilot Order</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Table inside the modal -->
        <form id="orderForm-orderModalNonDecoy" action="{{ route('decoy::updateOrder') }}" method="POST">
        @csrf
          <table class="table table-hover table-striped">
            @foreach ($nonDecoy as $pilot)
              <tr>
                <td>
                  <button type="button" class="up-btn" data-pilot="{{ $pilot['name'] }}" data-character_id="{{ $pilot['character_id'] }}">↑</button>
                  <button type="button" class="down-btn" data-pilot="{{ $pilot['name'] }}" data-character_id="{{ $pilot['character_id'] }}">↓</button>
                </td>
                <td>
                  <span>{{ $pilot['name'] }}</span>
                  <!-- Hidden input to store the pilot's character_id -->
                  <input type="hidden" name="order[{{ $pilot['character_id'] }}][name]" value="{{ $pilot['name'] }}" />
                  <input type="hidden" name="order[{{ $pilot['character_id'] }}][character_id]" value="{{ $pilot['character_id'] }}" />
                  <input type="hidden" name="order[{{ $pilot['character_id'] }}][order]" value="{{ $pilot['order'] }}" />
                </td>
              </tr>
            @endforeach
          </table>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveOrder-orderModalNonDecoy">Save Order</button>
        </div>
    </div>
  </div>
</div>

<!-- Modal Structure -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="filterModalLabel">Column Visibility</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="filterForm" action="{{ route('decoy::updateFilter') }}" method="POST">
          @csrf
          <input type="hidden" name="filter" id="filterInput" value="{{ $filterString }}"> 
          <div class="col-lg-9 col-md-6 col-sm-6">
            @foreach(['Security', 'Home Station', 'Skills', 'Faction Standings', 'PvP', 'Total Isk', 'Sell Orders', 'Isk Income', 'Isk Breakdown', 'Mining Amount', 'Industry Slots', 'Planetary Interaction'] as $index => $label)
              <label>
                <input type="checkbox" class="column-toggle" data-column="{{ $index + 2 }}" {{ $filterString[$index] == '1' ? 'checked' : '' }}>
                {{ $label }}
              </label><br />
            @endforeach
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Save Filter</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection