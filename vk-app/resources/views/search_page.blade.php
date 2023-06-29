@extends('layouts.app')

@section('content')
    <div class="main-content">
        @if(!empty($commentsArray))
            <div class="header" style="margin-left: 100px">
                <h1>Комментарии пользователя: <h4>{{$user[0]['last_name']}} {{$user[0]['first_name']}}</h4></h1>
                @for($i = 0,$iMax = count($commentsArray); $i < $iMax ; $i++)
                    <div style="margin-top: 20px">
                        <h3>{{$str}}: {{$commentsArray[$i][0]}}</h3>
                        <h5>Пост: {{$commentsArray[$i][1]}}</h5>
                        <div>Комментарий: </div>
                        @if($gradeArray[$i] >= 0)
                            <div style="color:green;">{{$commentsArray[$i]['text']}}</div>
                        @else
                            <div style="color:red;">{{$commentsArray[$i]['text']}}</div>
                        @endif
                    </div>
                @endfor
            </div>
        @endif
        @if(!empty($postsArray))
            <div class="header" style="margin-left: 100px">
                <h1>Посты пользователя {{$user[0]['last_name']}} {{$user[0]['first_name']}}</h1>
                @for($i = 0,$iMax = count($postsArray); $i < $iMax ; $i++)
                    <h3>{{$str}}: {{$postsArray[$i][0]}}</h3>
                    <h5>Пост:</h5>
                    @if($gradeArray[$i] >= 0.1)
                        <h5 style="color: green">{{$postsArray[$i]['text']}}</h5>
                    @elseif($gradeArray[$i] <= 0.1 && $gradeArray[$i] >= 0)
                        <h5 style="color: orange">{{$postsArray[$i]['text']}}</h5>
                    @else
                        <h5 style="color: red">{{$postsArray[$i]['text']}}</h5>
                    @endif
                @endfor
            </div>
        @endif
        @if(empty($user))
            <div class="header" style="margin-left: 100px">
                <h1>Пользователя не существует</h1>
            </div>
        @endif
        @if((empty($commentsArray)) && (empty($postsArray)) && (!empty($user)))
            <div class="header" style="margin-left: 100px">
                <h1>Пользователь {{$user[0]['last_name']}} {{$user[0]['first_name']}} не писал комментарии и не создавал
                    посты в группах, на которые он подписан</h1>
            </div>
        @endif
    </div>

    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
            aria-controls="offcanvasBottom" style="margin-top: 50px; margin-left: 100px">Узнать состояние пользователя
    </button>

    <div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasBottom" aria-labelledby="offcanvasBottomLabel">
        <div class="offcanvas-header">
            <h3 class="offcanvas-title" id="offcanvasBottomLabel">Состояние
                пользователя: {{$user[0]['last_name']}} {{$user[0]['first_name']}}</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body small">
            <h4>{{$gradeString}}</h4>
        </div>
    </div>

@endsection
