@if(session('message_success'))
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-success alert-dismissable">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>{{session('message_success')}}</strong>
            </div>
        </div>
    </div>
@endif

@if(session('message_danger'))
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-danger alert-dismissable">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>{{session('message_danger')}}</strong>
            </div>
        </div>
    </div>
@endif
