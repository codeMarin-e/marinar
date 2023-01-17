@isset($inputBag)
    @foreach($errors->$inputBag->all() as $error)
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-danger alert-dismissable">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <strong>{{ $error }}</strong>
                </div>
            </div>
        </div>
    @endforeach
@else
    @foreach($errors->all() as $error)
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-danger alert-dismissable">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <strong>{{ $error }}</strong>
                </div>
            </div>
        </div>
    @endforeach
@endisset
