@extends('web::layouts.grids.12')
@section('title', 'Combat Dashboard')
@section('page_header', '')
@section('page_description', 'Combat Dashboard')

@section('content')

<div class="row"><!-- row -->
    <div class="col-md-12">
        <div class="card card-default">
        <div class="card-header bg-success"><h3 class="card-title">Decoy Top Killers - Last 30 Days</h3></div>
            <div class="card-body">
                <div class="row">
                @foreach($formattedCharacterList as $ledger)
                <div class="col-md-3 col-sm-4">
                    <div class="info-box">
                    <span class="info-box-icon elevation-1"><img src="//images.evetech.net/characters/{{ $ledger->main_character_id }}/portrait?size=64"></span> <!-- Change from $ledger->id -->
                    <div class="info-box-content">
                        <span class="info-box-text">{{ $ledger->name }}</span> <!-- Change from $ledger->name -->
                        <span class="info-box-number">{{ $ledger->killmails }}</span> <!-- Change from $ledger->killmail_count -->
                        </div><!-- /.info-box-content -->
                    </div><!-- /.info-box -->
                </div><!-- /.col-md-4 col-sm-6 -->
                @endforeach
                </div>
            </div>
        </div>
    </div>
</div><!-- /row -->

<div class="row"><!-- row -->
    <div class="col-md-12">
        <div class="card card-default">
        <div class="card-header bg-info"><h3 class="card-title">Friendly Alliances - Last 7 Days</h3></div>
            <div class="card-body">
                <div class="row">
                @foreach($killmailLedgerFriendly as $ledger)
                <div class="col-md-3 col-sm-4">
                    <div class="info-box">
                    <span class="info-box-icon elevation-1"><img src="//images.evetech.net/alliances/{{ $ledger->alliance_id }}/logo?size=64"></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ $ledger->name }}</span>
                        <span class="info-box-number">{{ $ledger->count }}</span>
                    </div><!-- /.info-box-content -->
                    </div><!-- /.info-box -->
                </div><!-- /.col-md-4 col-sm-6 -->
                @endforeach
                </div>
            </div>
        </div>
    </div>
</div><!-- /row -->


<div class="row"><!-- row -->
    <div class="col-md-12">
        <div class="card card-default">
            <div class="card-header  bg-secondary"><h3 class="card-title">Neutral Alliances - Last 7 Days</h3></div>
            <div class="card-body">
                <div class="row">
                @foreach($killmailLedgerNeutral as $ledger)
                <div class="col-md-3 col-sm-4">
                    <div class="info-box">
                    <span class="info-box-icon elevation-1"><img src="//images.evetech.net/alliances/{{ $ledger->alliance_id }}/logo?size=64"></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ $ledger->name }}</span>
                        <span class="info-box-number">{{ $ledger->count }}</span>
                    </div><!-- /.info-box-content -->
                    </div><!-- /.info-box -->
                </div><!-- /.col-md-4 col-sm-6 -->
                @endforeach
                </div>
            </div>
        </div>
    </div>
</div><!-- /row -->

<div class="row"><!-- row -->
    <div class="col-md-12">
        <div class="card card-default">
        <div class="card-header bg-danger"><h3 class="card-title">Hostile Alliances - Last 7 Days</h3></div>
            <div class="card-body">
                <div class="row">
                @foreach($killmailLedgerHostile as $ledger)
                <div class="col-md-3 col-sm-4">
                    <div class="info-box">
                    <span class="info-box-icon elevation-1"><img src="//images.evetech.net/alliances/{{ $ledger->alliance_id }}/logo?size=64"></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ $ledger->name }}</span>
                        <span class="info-box-number">{{ $ledger->count }}</span>
                    </div><!-- /.info-box-content -->
                    </div><!-- /.info-box -->
                </div><!-- /.col-md-4 col-sm-6 -->
                @endforeach
                </div>
            </div>
        </div>
    </div>
</div><!-- /row -->

</div><!-- /row -->

@endsection