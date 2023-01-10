<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style type="text/css">
        html, body {
            font-size: 12px;
            font-family: Tahoma, Arial, Verdana, sans-serif;
        }

        table {
            font-size: 12px;
            border-top: 1px solid #DEDEDE;
            border-left: 1px solid #DEDEDE;
            color: #4B3B3C;
        }

        td {
            border-bottom: 1px solid #DEDEDE;
            border-right: 1px solid #DEDEDE;
            padding: 2px 4px;
        }

        .boxTable td {
            border: none;
        }
    </style>
</head>
<body>

<table cellpadding="0" cellspacing="0" border="0" style="width:800px; margin:0px auto; border: none;" class="boxTable">
    <tr>
        <td>

{{--            @if($logo = $chSite->getMainAttachment('logo'))--}}
{{--                <img src="{{$logo->getThumbnail('190x108')->getUrl()}}" alt="{{$chSite->domain}}" />--}}
{{--            @endif--}}
        </td>
    </tr>
</table>
<br/>
<table cellpadding="0" cellspacing="0" border="0" style="width:800px; margin:20px auto; border: none;" class="boxTable">
    <tr>
        <td>
            <div style="padding:2px; border:solid 1px #DEDEDE;">
                {{$slot}}
            </div>
        </td>
    </tr>
</table>
@if(isset($print))
    <script language="javascript">
        window.print();
    </script>
@endif
</body>
</html>
