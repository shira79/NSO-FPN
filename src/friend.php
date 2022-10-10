<?php

class Friend
{
    const STORAGE_PATH = 'storage/friend/';

    const STATE_STAMP_OFF = '🛌🛌🛌🛌🛌🛌🛌🛌🛌';
    const STATE_STAMP_ON  = '💪💪💪💪💪💪💪💪💪';

    const KNOWN_GAME_SHORT_NAME_LIST = [
        '大乱闘スマッシュブラザーズ SPECIAL'         => 'スマブラ',
        'ポケットモンスター ブリリアントダイヤモンド'   => 'ポケモン ダイヤモンド',
        'ポケットモンスター シャイニングパール'        => 'ポケモン パール',
        'マリオカート８ デラックス'                  => 'マリカー8',
        '桃太郎電鉄 ～昭和 平成 令和も定番！～'        => '桃鉄',
        'スーパーファミコン Nintendo Switch Online' => 'スーファミ',
    ];

    const NO_GAME_NAME = 'NO_GAME!!!!!!';

    private string $nsaId;
    // private string $imageUri;
    // private bool $isFriend;
    private string $name;
    private bool $isFavoriteFriend;
    // private bool $isServiceUser;
    // private int $friendCreatedAt;
    private string $state;
    private ?string $game;

    private string $stateFilepath;

    public function __construct(array $friendData)
    {
        $this->nsaId = $friendData['nsaId'];
        $this->imageUri = $friendData['imageUri'];
        $this->name = $friendData['name'];
        $this->isFavoriteFriend = $friendData['isFavoriteFriend'];
        $this->state = $friendData['presence']['state'];
        $this->game = $friendData['presence']['game']['name'] ?? self::NO_GAME_NAME;

        $this->stateFilepath = self::STORAGE_PATH.$this->nsaId;
    }

    public function __destruct(){
        file_put_contents($this->stateFilepath, $this->game);
    }

    public function isFavoriteFriend():bool
    {
        return $this->isFavoriteFriend;
    }

    private function getThisTimeGame():string
    {
        return $this->game;
    }

    private function getLastTimeGame():string
    {
        $value = file_get_contents($this->stateFilepath);

        if($value === false){
            return self::NO_GAME_NAME;
        }
        return $value;
    }

    public function isSwitched():bool
    {
        return $this->getThisTimeGame() !== $this->getLastTimeGame();
    }

    private function getGameDisplay():string
    {
        if($this->game == self::NO_GAME_NAME){
            return '';
        }
        if(!is_null($short = self::KNOWN_GAME_SHORT_NAME_LIST[$this->game])){
            return $short;
        }
        return $this->game;
    }

    public function notify(Notification $notification, array $parameter)
    {
        $notification->setParameter($parameter);
        $notification->send();
    }

    public function generateMessage():string
    {
        $text = "\n".'name: '.$this->name.
                "\n".'state: '.$this->state;

        if($this->game != self::NO_GAME_NAME){
            $text = $text . "\n".'game: '.$this->getGameDisplay();
            $text = $text . "\n". self::STATE_STAMP_ON;
        } else {
            $text = $text . "\n". self::STATE_STAMP_OFF;
        }

        return $text;
    }
}