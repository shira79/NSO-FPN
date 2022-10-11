# NSO-FPN
**Nintendo Switch Online - Friend Presence Notifier**

[任天堂Switchのフレンドのオンライン状態が変わったらLINE通知する](https://zenn.dev/shlia/articles/69f677f2fe0e91)

<img width="446" alt="スクリーンショット 2022-10-10 22 03 43" src="https://user-images.githubusercontent.com/42834409/194895511-902c6fad-d9c7-44b0-adbc-eb110956b924.png">

## 準備
### 必要なもの
- Nintendo　Switch Onlineのアカウントのsession_token
- [Line Notify](https://notify-bot.line.me/doc/ja/)のaccess_token
- Docker

※ 現在、session_tokenを取得する機能は未実装なので、[mitmproxy](https://mitmproxy.org/)や[Proxyman](https://proxyman.io/)などのプロキシツールから取得しなくちゃいけない。

### .env
```
cp src/.env.sample src/.env
vim src/.env
```

### docker
```
docker build -t nso-fpn:latest .
docker run --rm  -v `pwd`/src:/var/www/html nso-fpn
docker ps
docker exec -it <name> bash
```

## 仕様
- ５分毎にフレンドの状態を取得するAPIを叩いてる。
- ゲームが切り替わるor新しくゲームを始めたorやめた場合に通知。
- 任天堂のAPI以外に、[fトークンを取得するためのAPI](https://github.com/JoneWang/imink/wiki/imink-API-Documentation)を叩いてる。（利用させていただきありがとうございます）

## 任天堂関連のAPIで参考にさせていただいたページ

- https://github.com/samuelthomas2774/nxapi
- https://gitlab.fancy.org.uk/samuel/nxapi/-/wikis/Nintendo-tokens#nintendo-account-session-token
- https://github.com/MCMi460/NSO-RPC
- https://github.com/frozenpandaman/s3s
- https://github.com/JoneWang/imink/wiki/FAQ#login-security
- https://github.com/ZekeSnider/NintendoSwitchRESTAPI#authentication-steps
- https://dev.to/mathewthe2/intro-to-nintendo-switch-rest-api-2cm7
- https://github.com/ZekeSnider/NintendoSwitchRESTAPI
