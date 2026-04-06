<?php 
namespace App\Service;
class MessageGenerator
{public function getHappyMessage():string 
{$messages= [
    'Believe you van abd you are halfway there ',
    'The best way to predict the future is to create it.',
    'Great work keep going !',
];
$index=array_rand($messages);
return $messages[$index];}
}