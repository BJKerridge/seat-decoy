@extends('web::layouts.grids.12')

@section('title', 'Mumble Connector')
@section('page_header', '')
@section('page_description', 'Mumble Connector')

@section('content')
<div class="container">
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h3>Connection Information</h3>
          <div class="form-horizontal">
            <div class="form-group">
              <label class="col-sm-3 control-label text-left">Server</label>
              <div class="col-sm-9">
                <input type="text" class="form-control" value="mumble.xdecoyx.com" readonly onclick="this.select();">
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label text-left">Port</label>
              <div class="col-sm-9">
                <input type="text" class="form-control" value="64738" readonly onclick="this.select();">
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label text-left">Username</label>
              <div class="col-sm-9">
                <input type="text" class="form-control" id="mumbleUsername" value="[{{ $ticker }}] {{ $characterName }}" readonly onclick="this.select();">
              </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label text-left">Password</label>
                <div class="col-sm-9">
                  <div class="input-group">
                    <input type="password" id="mumblePassword" name="mumblePassword" class="form-control" value="{{ $passwordGenerator }}" required minlength="8">
                          <div class="input-group-append">
                              <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                  <i class="fa fa-eye" aria-hidden="true"></i>
                              </button>
                          </div>
                      </div>
                  </div>
                      <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                  </div>

            <div class="info-box-number" style="margin-top: 10px;">
              <button type="button" class="btn btn-success btn-sm"  id="connectBtn">Connect</button>
            </div>
          </div>
        </div>
      </div> 
    </div>
  </div>
</div>


<script>
const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#mumblePassword');

togglePassword.addEventListener('click', function () {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);

    this.querySelector('i').classList.toggle('fa-eye');
    this.querySelector('i').classList.toggle('fa-eye-slash');
});
</script>


<script>
document.getElementById('connectBtn').addEventListener('click', function() {
    const username = document.getElementById('mumbleUsername').value;
    const password = document.getElementById('mumblePassword').value;

    fetch('/mumble-proxy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ u: username, pass: password })
    })
    .then(async resp => {
        let data;
        try {
            data = await resp.json();
        } catch {
            throw new Error('Invalid response from server');
        }

        if (!resp.ok || data.status !== 'ok') {
            throw new Error(data.message || 'Mumble login failed');
        }

        // SUCCESS → redirect
        window.location.href =
            `mumble://${encodeURIComponent(username)}:${encodeURIComponent(password)}@mumble.xdecoyx.com:64738`;
    })
    .catch(err => {
        alert(err.message);
        console.error(err);
    });
});
</script>


@endsection