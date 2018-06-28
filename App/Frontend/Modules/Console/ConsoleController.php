<?php
namespace App\Frontend\Modules\Console;

use \OCFram\BackController;
use \OCFram\HTTPRequest;
use \Materialize\WidgetFactory;
use \Materialize\FloatingActionButton;
use \Materialize\FlatButton;
use \Debug\Log;


class ConsoleController extends BackController
{
  public function executeIndex(HTTPRequest $request)
  {

    $this->page->addVar('title', 'Console DomusBox');

    $sendButton = new FlatButton([
      'id'=>'send-command',
      'title'=>'Envoyer',
      'icon'=>'send',
      'color'=>'primaryTextColor',
      'type'=>'button'
    ]);

    $address='192.168.1.52';
    $port=5901;

    $url="http://$address/dashboard/resultat.php?log";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $log = curl_exec($ch);



    $this->page->addVar('sendButton',$sendButton);
    $this->page->addVar('log',$log);
  }

}