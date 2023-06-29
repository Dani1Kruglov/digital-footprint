<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use Illuminate\Http\Request;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiPrivateProfileException;
use VK\Exceptions\Api\VKApiWallAccessRepliesException;
use VK\OAuth\Scopes\VKOAuthGroupScope;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\VKOAuthResponseType;
use VK\Exceptions\Api\VKApiTooManyException;
require  '../vendor/autoload.php';

class HomeController extends Controller
{


    private string $access_token = 'ACCESS_TOKEN';

    public function index(){
        return view('home');
    }




    public function searchById(SearchRequest $request){
        $data = $request->validated();
        $user_id = $data['userId'];
        $vk = new VKApiClient();
        $access_token = $this->access_token;
        $user = $vk->users()->get($access_token, array(
            'user_ids'=>$user_id
        ));


        if ((int)$data['mark'] === 0){
            $obj = $this->group($vk, $access_token, $user_id);
        }elseif((int)$data['mark'] === 1){
            $obj = $this->friends($vk, $access_token, $user_id);
        }
        $postsArray = $obj->postsArray;
        $commentsArray = $obj->commentsArray;
        $gradeString = $obj->gradeString;
        $str = $obj->str;
        $gradeArray = $obj->gradeArray;


        return view('search_page', compact('postsArray', 'commentsArray', 'user', 'gradeString', 'str', 'gradeArray'));
    }

    public function group($vk, $access_token, $user_id)
    {
        $groups = $vk->groups()->get($access_token, array(
            'user_id'=>$user_id,
            'extended'=>1,
            'filter'=>'publics',
        ));
        $commentsArray = array();
        $postsArray = array();
        $count = 50;
        $countComments = 50;
        $temp = 0;
        for ($i = 0; $i < (int)$groups['count']; $i++) {
            $owner_id = (int)$groups['items'][$i]['id'];
            $j = $temp;
            try {
                $response = $vk->wall()->get($access_token, array(
                    'owner_id'=>-$owner_id,
                    'count'=>$count,
                ));
                $posts = $response['items'];
                if ($response !== false) {
                    for ( $jMax = count($posts); $j < $jMax; $j++) {
                        if (isset($posts[$j]['signer_id']) && ($posts[$j]['signer_id'] === (int)$user_id)) {
                            $posts[$j][] = $groups['items'][$i]['name'];
                            $postsArray[] = $posts[$j];
                        }

                        $response = $vk->wall()->getComments($access_token, array(
                            'owner_id'=> -$owner_id,
                            'post_id'=>$posts[$j]['id'],
                            'count' => $countComments,
                        ));

                        $comments = $response['items'];
                        foreach ($comments as $comment){
                            if ($comment['from_id'] === (int)$user_id){
                                $comment[] = $groups['items'][$i]['name'];
                                $comment[] = $posts[$j]['text'];
                                $commentsArray[] = $comment;
                            }
                        }

                        if ($j + 1 === $jMax){
                            $temp = 0;
                        }
                    }
                }
            } catch (VKApiTooManyException $err) {
                if ($err->getCode() == 6) {
                    sleep(1);
                    if ($j + 1 === $jMax){
                        $temp = 0;
                    }
                    else{
                        $temp = $j+1;
                        $i--;
                    }

                }
            }

        }

        $obj = $this->grade($commentsArray, $postsArray);
        $gradeString = $obj->gradeString;
        $gradeArray = $obj->gradeArray;
        $str = "Сообщество";

        return (object) compact('postsArray', 'commentsArray', 'gradeString', 'str', 'gradeArray');
    }


    public function friends($vk, $access_token, $user_id){
        $users = $vk->friends()->get($access_token, array(
            'user_id'=>$user_id,
            'extended'=>1,
            'fields'=>'can_post',
        ));
        $commentsArray = array();
        $postsArray = array();
        $count = 50;
        $countComments = 50;
        $temp = 0;
        for ($i = 0; $i < (int)$users['count']; $i++) {
            $owner_id = (int)$users['items'][$i]['id'];
            $j = $temp;
            try {
                try {
                    $response = $vk->wall()->get($access_token, array(
                        'owner_id'=>$owner_id,
                        'count'=>$count,
                    ));
                }catch (VKApiPrivateProfileException $err){
                    if ( $err->getMessage() === 'This profile is private'){

                        $i++;
                    }
                }
                $posts = $response['items'];
                if ($response !== false) {
                    for ( $jMax = count($posts); $j < $jMax; $j++) {
                        if (isset($posts[$j]['signer_id']) && ($posts[$j]['signer_id'] === (int)$user_id)) {
                            $posts[$j][] = $users['items'][$i]['last_name'];
                            $postsArray[] = $posts[$j];
                        }

                        try {
                            $response = $vk->wall()->getComments($access_token, array(
                                'owner_id'=>$owner_id,
                                'post_id'=>$posts[$j]['id'],
                                'count' => $countComments,
                            ));
                        }catch (VKApiWallAccessRepliesException $err){
                            if ( $err->getMessage() === 'Access to post comments denied'){
                                break;
                            }

                        }

                        $comments = $response['items'];
                        foreach ($comments as $comment){
                            if ($comment['from_id'] === (int)$user_id){
                                $comment[] = $users['items'][$i]['last_name'];
                                $comment[] = $posts[$j]['text'];
                                $commentsArray[] = $comment;
                            }
                        }

                        if ($j + 1 === $jMax){
                            $temp = 0;
                        }
                    }
                }
            } catch (VKApiTooManyException $err) {
                if ($err->getCode() == 6) {
                    sleep(1);
                    if ($j + 1 === $jMax){
                        $temp = 0;
                    }
                    else{
                        $temp = $j+1;
                        $i--;
                    }

                }
            }

        }

        $gradeString = $this->grade($commentsArray, $postsArray);
        $str = "Пользователь";
        return (object) compact('postsArray', 'commentsArray', 'gradeString', 'str');
    }


    public function grade($commentsArray, $postsArray){
        if((!empty($postsArray)) && (!empty($commentsArray))){
            $userActionsArray = array_merge($commentsArray, $postsArray);
        }
        elseif(!empty($postsArray)){
            $userActionsArray = $postsArray;
        }elseif (!empty($commentsArray)){
            $userActionsArray = $commentsArray;
        }

        $gradeArray = [];



       $goodWords = array('акци','аттракцион','безмятежн','бесплатн','благодарн','благородств','блаженств','богатств',
           'бодр','божествен','бонус','бриллиант','везени','вер','весел',
           'весн','вкусня','восторг','восхищени','выигрыш','выходны','гармони',
           'гонорар','горд ','гор','гостеприим','деньг','дет','добр',
           'достат','достиж','дружб','друз','женить','забав',
           'забот','заначк','здоров','знакомств','игр','кайф','каникул',
           'хорош','комед','конфет','корпоратив','красот','креатив','ласк',
           'лёгко','лет','лидер','лучш', 'люб',
           'мам','массаж', 'мечта','мир','море','мороженое',
           'наград','надеж', 'наслажд',
           'нежн','новый год','обнов','объят','отд','отпуск',
           'побед','дар','позитив','поощрени','потех',
           'поцел','праздник','преданность','презент','прекрасн','приз',
           'рад','рай','релакс','радуг','свет','свобод',
           'семь','смех','счаст','эйфори','пят','отл',
           'рад','здоров','крас');
       $badWords = array('авари','авиакатастроф',' ад','банкротств','бегств','бедност','беспокойств','болезн',
           'боль','вирус','войн','гнил','горе','гряз','жертв','завист',
           'злост','измен','катастроф','краж','крах','кризис','кровопролити',
           'маньяк','могил','насил','ненавист','нищет','обид','обман',
           'ограблени','одиночеств','подстав','позор','предатель','провал','проигрыш',
           'пытк','развод','разрушени','самоубийств','санкци','скук','слаб',
           'слез','смерт','ссор','страдани','страх','суд','суицид',
           'теракт','тоск','тревог','трус','тюрьм','убийств',
           'ущерб','яд','плох','боль','двойк','неуд','долг',
           'умер', 'ужас', 'погиб','вор');
       $totalState = 0.0;  //Настроение по всем постам
       $onlyOneTypeOfState = 0;    //1 - только позитив, -1 - только негатив, 2 - смешанные, 0 - нейтральный
       foreach($userActionsArray as $userAction)
       {
           $usersState = 0.0;    //Состояние пользователя, изначально - нейтральное
           $text = mb_strtolower($userAction['text']);
           $numWords = 0;
           $lastSymbol = '';
           foreach(str_split($text) as $char)   //Подсчитываем число слов в тексте
           {
               if($char === ' ')
               {
                   if($lastSymbol === '')
                   {
                       $numWords++;
                       $lastSymbol = ' ';
                   }
               }
               else {$lastSymbol = '';}
           }
           if($lastSymbol != ' ') {$numWords++;}
           //Если не имеем пустой текст
           if($numWords != 0)
           {
               foreach($goodWords as $word)    //циклом считаем совпадения хороших корней
               {
                   $numFound = mb_substr_count($text, $word, 'UTF-8');
                   $usersState += $numFound;
                   if(($onlyOneTypeOfState != 2) && ($numFound != 0))
                   {
                       if($onlyOneTypeOfState === 0) {$onlyOneTypeOfState = 1;}
                       else {$onlyOneTypeOfState = 2;}
                   }
               }
               foreach($badWords as $word) //циклом считаем совпадения плохих корней
               {
                   $numFound = mb_substr_count($text, $word, 'UTF-8');
                   $usersState -= $numFound;
                   if(($onlyOneTypeOfState != 2) && ($numFound != 0))
                   {
                       if($onlyOneTypeOfState === 0) {$onlyOneTypeOfState = -1;}
                       else {$onlyOneTypeOfState = 2;}
                   }
               }
               $usersState = bcdiv($usersState, $numWords, 3); //делим абсолютную сумму совпадений на число слов в тексте
               $totalState+=$usersState;

               $gradeArray[] = $usersState;
           }
       }
       if($onlyOneTypeOfState === 1) {$grade =  "Позитивное";}
       else
       {
           if($onlyOneTypeOfState === -1) {$grade =  "Негативное";}
           else { $grade =  ($totalState <= -0.3) ? ("Негативное") : (($totalState >= 0.3) ? "Позитивное" : "Нейтральное");}
       }

       $gradeString = "Настроение пользователя: $grade";
        return (object) compact('gradeArray', 'gradeString');
    }
}
