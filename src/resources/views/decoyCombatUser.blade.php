@extends('web::layouts.grids.12')
@section('title', 'Combat Dashboard')
@section('page_header', '')
@section('page_description', 'Combat Dashboard')

@section('content')

<style>
.circle {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    border: 1px solid black;
}
.circle-blue { background-color: #3a9aeb; }
.circle-green { background-color: #71e554; }
.circle-yellow { background-color: #f3fd82; }
.circle-orange { background-color: #ce440f; }
.circle-red { background-color: #722020; }
.circle-darkred { background-color:  #8d3264; }
</style>

<div class="row">
    

    <!-- Online Badge -->
    <div class="col-md-4 col-sm-6">
      <div class="info-box">
        <span class="info-box-icon bg-aqua elevation-1"><img src="//images.evetech.net/characters/{{ $id }}/portrait?size=64"></span>
        <div class="info-box-content">
            <span class="info-box-text">Main Pilot</span>
            <span class="info-box-number">{{ $user->name }}</span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>

    <!-- Online Badge -->
    <div class="col-md-4 col-sm-6">
      <div class="info-box">
        <span class="info-box-icon bg-aqua elevation-1"><i class="fas fa-skull-crossbones"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">Killmails over the last 30 days</span>
            <span class="info-box-number">{{ count(json_decode($user->killmails)) }}</span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>
    
    <!-- Online Badge -->
    <div class="col-md-4 col-sm-6">
      <div class="info-box">
        <span class="info-box-icon bg-aqua elevation-1"><i class="fas fa-coins"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">Value destroyed over the last 30 days</span>
          <span class="info-box-number">{{ $total_kill_value }}</span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>
</div>

    <!-- TESTER ROW -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body" style="line-height: 12px; font-size: 12px;">
                    <h4 class="text-center">Kills</h4>
                    <table class="table table-hover">
                        <thead><tr>
                            <th style="width: 120px;">Time</th>
                            <th style="width: 96px;">System</th>
                            <th style="width: 32px;">Alliance</th>
                            <th style="width: 80px;">Corp</th>
                            <th style="width: 32px;">Ship</th>
                            <th style="width: 32px;">Value</th>
                            <th>Pilots</th>
                        </tr></thead>
                        <tbody>
                        @foreach ($killmailData as $killmail)
                        <tr onclick="window.open('https://zkillboard.com/kill/{{ $killmail['killmail_id'] }}/', '_blank');" style="cursor: pointer;">
                        <td>{{ $killmail['killmail_time'] }}</td>
                        <td>{{ $killmail['killmail_location'] }}<br /><div class="circle
                            @if ($killmail['killmail_location_sec'] >= 0.8)
                                circle-blue
                            @elseif ($killmail['killmail_location_sec'] >= 0.5)
                                circle-green
                            @elseif ($killmail['killmail_location_sec'] >= 0.3)
                                circle-yellow
                            @elseif ($killmail['killmail_location_sec'] > 0)
                                circle-orange
                            @elseif ($killmail['killmail_location_sec'] == -0.3)
                                circle-red
                            @else
                                circle-darkred
                            @endif
                        "></div> {{ $killmail['killmail_location_sec'] }}</td>
                        <td><img src="https://images.evetech.net/alliances/{{ $killmail['alliance_id'] }}/logo?size=32" class="img-circle"></td>
                        <td><img src="https://images.evetech.net/corporations/{{ $killmail['corporation_id'] }}/logo?size=32" class="img-circle"></td>
                        <td><img src="https://images.evetech.net/types/{{ $killmail['ship_type_id'] }}/render?size=32" class="img-circle elevation-2"></td>
                        <td> {{ $killmail['kill_value'] }} </td>
                        <td>@foreach ($killmail['attacker_ids'] as $attacker)
                        <span><img src="//images.evetech.net/characters/{{ $attacker['attacker_id'] }}/portrait?size=32" title="{{ $attacker['attacker_name'] }}" class="img-circle"></span>
                        @endforeach</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div><!-- /row -->

@endsection
