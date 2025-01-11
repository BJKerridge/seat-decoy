@extends('web::layouts.grids.12')

@section('title', 'Structure Dashboard')
@section('page_header', '')
@section('page_description', 'Structure Dashboard')

@section('content')
<style>
.table td, .table th{
    padding: 0.5rem;
}
</style>

<div class="row">
<div class="col-12 col-lg-12">
    <div class="card">
           <div class="card-body">
               Below is the time citadels will go low-power, how much Liquid Ozone and Reagents we have in our citadels.<br />
               <br />
               <b>30 Days</b> is the target fuel amount for citadels.<br />
               <b>14 Days of Fuel or less</b> will highlight the row yellow.<br />
               <b>7 Days of Fuel or less</b> will highlight the row red.<br />
               <br />
               <b>Ansiblex</b> should aim to have 750,000 units of Liquid Ozone, but double in the critical routes (IAK, 2-K and GN-).<br />
               <b>Metenox</b> should aim to have 50,000 units of Magmatic Gas.
               <br />
               <br />
               <b>Starbase Fuel</b> should be 20 days worth (5k for a small, 10k for a medium, 20k for a large)<br />
               <b>Starbase Stront</b> should be full (5k for a small, 10k for a medium, 20k for a large)<br />
           </div>
       </div>
       </div>
</div>
<div class="row">

   <!-- Existing DataTable for $dataTable -->
   <div class="col-12 col-lg-6">
       <div class="card">
           <div class="card-body" style="line-height: 12px; font-size: 12px;">
           <h4 class="text-center">Structures</h4>
               {!! $dataTable->table(['class' => 'table table-hover']) !!}
           </div>
       </div>
   </div>

   <!-- DataTable for StructureID = 35841 -->
   <div class="col-12 col-lg-6">
       <div class="card">
           <div class="card-body" style="line-height: 12px; font-size: 12px;">
               <h4 class="text-center">Ansiblex</h4>
               <table id="ansiblexTable" class="table table-hover">
                   <thead>
                       <tr>
                           <th> </th>
                           <th>Structure Name</th>
                           <th>Fueled Until</th>
                           <th>Liquid Ozone</th>
                       </tr>
                   </thead>
                   <tbody>
                       <!-- Data for StructureID = 35841 will be populated here -->
                   </tbody>
               </table>
           </div>

           <div class="card-body" style="line-height: 12px; font-size: 12px;">
               <h4 class="text-center">Metenox</h4>
               <table id="metenoxTable" class="table table-hover">
                   <thead>
                       <tr>
                           <th> </th>
                           <th>Structure Name</th>
                           <th>Fueled Until</th>
                           <th>Magmatic Gas</th>
                       </tr>
                   </thead>
                   <tbody>
                       <!-- Data for StructureID = 81826 will be populated here -->
                   </tbody>
               </table>
           </div>

           <div class="card-body" style="line-height: 12px; font-size: 12px;">
               <h4 class="text-center">Starbases</h4>
               <table id="starbaseTable" class="table table-hover">
                   <thead>
                       <tr>
                           <th> </th>
                           <th>Location</th>
                           <th>Fueled Until</th>
                           <th>Strontium</th>
                       </tr>
                   </thead>
                   <tbody>
                   </tbody>
               </table>
           </div>
       </div>


   </div>

</div><!-- /row -->
@endsection

@push('javascript')
{!! $dataTable->scripts() !!}

<script>
    $(document).ready(function () {
        function settingsChanged() {
            window.LaravelDataTables['dataTableBuilder'].ajax.reload();
            const text = $("#dt-item-selector").select2("data")[0].text;
            $('#order-card-header').text(text)
        }

        // Parse the JSON data for the second DataTable
        var jsonData = {!! json_encode($jsonOut) !!};
        var starbaseData = {!! json_encode($starbaseList) !!};

        // Filter data for StructureID = 35841
        var ansiblexData = jsonData.filter(function(item) {
            return item.StructureType == 35841;
        });

        // Filter data for StructureID = 81826
        var metenoxData = jsonData.filter(function(item) {
            return item.StructureType == 81826;
        });

        function highlightRowIfNeeded(rowData) {
            if (moment(rowData.FueledUntil).isBetween(moment(), moment().add(7, 'days'), null, '[]')) {return 'table-danger';}
            else if (moment(rowData.FueledUntil).isBetween(moment(), moment().add(14, 'days'), null, '[]')) {return 'table-warning';}
            return '';  // No class if not within 14 days
        }

        function highlightLOIfNeeded(rowData) {
            if (rowData.quantity < 500000) {return 'table-danger';}
            else if(rowData.quantity < 700000) {return 'table-warning';}
            return '';  // No class if not within 7 days
        }

        // Initialize the first DataTable (ansiblexTable) for StructureID = 35841
        $('#ansiblexTable').DataTable({
            data: ansiblexData,
            paging: false,
            searching: false,
            info: false,
            columns: [
                { data: 'StructureType', render: function (data, type, row) {return '<img src="//images.evetech.net/types/35841/icon?size=32" class="img-circle eve-icon small-icon">';} },
                { data: 'StructureName' },
                { data: 'FueledUntil' },
                { data: 'quantity' }
            ],
            rowCallback: function(row, data, index) {
                var dangerClass = highlightRowIfNeeded(data); // Get class based on the Fueled date
                if (dangerClass) {
                    $(row).addClass(dangerClass); // Add 'danger' class to the row
                }
            },
            order: [[2, 'asc']]  // Default order by the second column (index 1), ascending
        });

        // Initialize the second DataTable (metenoxTable) for StructureID = 81826
        $('#metenoxTable').DataTable({
            data: metenoxData,  // Pass the filtered data for StructureID = 81826
            paging: false,    // Disable pagination
            searching: false, // Disable searching
            info: false,      // Disable info display
            columns: [
                { data: 'StructureType',
                    render: function (data, type, row) {
                        // Render image and text for StructureType 81826 (Metenox)
                        return '<img src="//images.evetech.net/types/81826/icon?size=32" class="img-circle eve-icon small-icon">';
                    } },  // Adjust column names based on your JSON structure
                { data: 'StructureName' },
                { data: 'FueledUntil' },
                { data: 'quantity' }
            ],
            rowCallback: function(row, data, index) {
                var dangerClass = highlightRowIfNeeded(data); // Get class based on the Fueled date
                if (dangerClass) {
                    $(row).addClass(dangerClass); // Add 'danger' class to the row
                }
            },
            order: [[2, 'asc']]  // Default order by the second column (index 1), ascending
        });


        $('#starbaseTable').DataTable({
            data: starbaseData,  // Pass the filtered data for StructureID = 81826
            paging: false,    // Disable pagination
            searching: false, // Disable searching
            info: false,      // Disable info display
            columns: [
                { data: 'POSType', render: function (data, type, row) {return `<img src="//images.evetech.net/types/${data}/icon?size=32" class="img-circle eve-icon small-icon">`;} },
                { data: 'Location' },
                { data: 'FueledUntil' },
                { data: 'UnanchorAt' }
            ],
            rowCallback: function(row, data, index) {
                var dangerClass = highlightRowIfNeeded(data); // Get class based on the Fueled date
                if (dangerClass) {
                    $(row).addClass(dangerClass); // Add 'danger' class to the row
                }
            },
            order: [[2, 'asc']]  // Default order by the second column (index 1), ascending
        });


    });
</script>
@endpush
