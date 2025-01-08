@extends('web::layouts.grids.12')

@section('title', 'Structure Dashboard')
@section('page_header', '')
@section('page_description', 'Structure Dashboard')

@section('content')
<div class="row"></div>

<div class="row">

   <!-- Existing DataTable for $dataTable -->
   <div class="col-12 col-lg-6">
       <div class="card">
           <div class="card-header text-center"><img src="decoy\img\fort.png" class="img-fluid center-block"/></div>
           <div class="card-body">
               {!! $dataTable->table(['class' => 'table table-hover']) !!}
           </div>
       </div>
   </div>

   <!-- DataTable for StructureID = 35841 -->
   <div class="col-12 col-lg-6">
       <div class="card">
           <div class="card-header text-center"><img src="decoy\img\ansi.png" class="img-fluid center-block"/></div>
           <div class="card-body">
               <h4 class="text-center">Ansiblex</h4>
               <table id="jsonTable35841" class="table table-hover">
                   <thead>
                       <tr>
                           <th>Type</th>
                           <th>Structure Name</th>
                           <th>Liquid Ozone</th>
                       </tr>
                   </thead>
                   <tbody>
                       <!-- Data for StructureID = 35841 will be populated here -->
                   </tbody>
               </table>
           </div>
       </div>

       <div class="card">
           <div class="card-header text-center"><img src="decoy\img\metenox.png" class="img-fluid center-block"/></div>
           <div class="card-body">
               <h4 class="text-center">Metenox</h4>
               <table id="jsonTable81826" class="table table-hover">
                   <thead>
                       <tr>
                           <th>Type</th>
                           <th>Structure Name</th>
                           <th>Magmatic Gas</th>
                           <th>Fueled Until</th>
                       </tr>
                   </thead>
                   <tbody>
                       <!-- Data for StructureID = 81826 will be populated here -->
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

        // Filter data for StructureID = 35841
        var data35841 = jsonData.filter(function(item) {
            return item.StructureType == 35841;
        });

        // Filter data for StructureID = 81826
        var data81826 = jsonData.filter(function(item) {
            return item.StructureType == 81826;
        });

        function highlightRowIfNeeded(rowData) {
            if (moment(rowData.Fueled).isBetween(moment(), moment().add(15, 'days'), null, '[]')) {return 'table-danger';}
            else if (moment(rowData.Fueled).isBetween(moment(), moment().add(16, 'days'), null, '[]')) {return 'table-warning';}
            return '';  // No class if not within 14 days
        }

        function highlightLOIfNeeded(rowData) {
            if (rowData.quantity < 500000) {return 'table-danger';}
            else if(rowData.quantity < 700000) {return 'table-warning';}
            return '';  // No class if not within 7 days
        }

        // Initialize the first DataTable (jsonTable35841) for StructureID = 35841
        $('#jsonTable35841').DataTable({
            data: data35841,  // Pass the filtered data for StructureID = 35841
            paging: false,    // Disable pagination
            searching: false, // Disable searching
            info: false,      // Disable info display
            columns: [
                { data: 'StructureType',
                    render: function (data, type, row) {
                        // Render image and text for StructureType 35841 (Ansiblex)
                        return '<img src="//images.evetech.net/types/35841/icon?size=32" class="img-circle eve-icon small-icon"> Ansiblex';
                    } },  // Adjust column names based on your JSON structure
                { data: 'StructureName' },
                { data: 'quantity' }
            ],
            rowCallback: function(row, data, index) {
                var dangerClass = highlightLOIfNeeded(data); // Get class based on the Fueled date
                if (dangerClass) {
                    $(row).addClass(dangerClass); // Add 'danger' class to the row
                }
            },
            order: [[2, 'asc']]  // Default order by the second column (index 1), ascending
        });

        // Initialize the second DataTable (jsonTable81826) for StructureID = 81826
        $('#jsonTable81826').DataTable({
            data: data81826,  // Pass the filtered data for StructureID = 81826
            paging: false,    // Disable pagination
            searching: false, // Disable searching
            info: false,      // Disable info display
            columns: [
                { data: 'StructureType',
                    render: function (data, type, row) {
                        // Render image and text for StructureType 81826 (Metenox)
                        return '<img src="//images.evetech.net/types/81826/icon?size=32" class="img-circle eve-icon small-icon"> Metenox';
                    } },  // Adjust column names based on your JSON structure
                { data: 'StructureName' },
                { data: 'quantity' },
                { data: 'Fueled' }
            ],
            // Row callback to apply 'danger' class
            rowCallback: function(row, data, index) {
                var dangerClass = highlightRowIfNeeded(data); // Get class based on the Fueled date
                if (dangerClass) {
                    $(row).addClass(dangerClass); // Add 'danger' class to the row
                }
            },
            order: [[3, 'asc']]  // Default order by the second column (index 1), ascending
        });
    });
</script>
@endpush
