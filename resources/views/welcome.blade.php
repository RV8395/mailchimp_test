<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <!-- Styles -->
    <style>
        html,
        body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            /* align-items: center; */
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 48px;
        }

        .links{
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
        }

        .links>a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="flex-center position-ref full-height">
        <div class="content">
            <div class="title m-b-md">
                Mailchimp Integration
            </div>

            @if ($msg = Session::get('success'))
            <div class="alert alert-success">
                <strong>{{ $msg }}</strong>
            </div>
            @endif

            @if (count($errors) > 0)
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="links">
                <p>Add New Contacts</p>
                <div class="row">
                    <form method="POST" action="{{route('import_contacts')}}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept="application/csv">
                        <input type="submit" class="btn btn-primary" value="Import" />
                    </form>
                </div>
            </div>

            <div class="links">
                <p>Update Contact Data</p>
                <div class="row">
                    <form method="POST" action="{{route('update_contacts')}}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept="application/csv">
                        <input type="submit" class="btn btn-primary" value="Sync Data" />
                    </form>
                </div>
            </div>

            <div class="links">
                <p>Sync Tags</p>
                <div class="row">
                    <form method="POST" action="{{route('sync_tags')}}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept="application/csv">
                        <input type="submit" class="btn btn-primary" value="Sync Tags" />
                    </form>
                </div>
            </div>

            <div class="links">
                <p>Export Contacts Data</p>
                <div class="row">
                    <a href="{{route('export_list')}}" class="btn btn-primary">Export</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>