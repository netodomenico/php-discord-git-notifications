# PHP Discord GIT Notifications
##### *Currently compatible with Github, BitBucket and Gitlab*

Send a Discord message on every push, pull request created or approved on your Git repository.

#### Features

- Send messages as notifications on your company Discord server.
- Stay up to date on the progress of a project under development.
- Setup a channel representing a customer on your Discord server and receive messages on every action.
- Set a *secure token* for your webhooks (only for GitLab repositories).
- Setup your custom configuration (bot name, color, avatar or message) and grab the attention of your colleagues!
- Declare the webhooks of **all** your repositories at the same time, even if they are from different Git providers.

#### Installation

**PHP Discord GIT Notifications** requires PHP 7.1+ to run.
Install the package and initialize your repository entries.

```sh
composer require netodomenico/php-discord-git-notifications
```

#### Configuration

**PHP Discord GIT Notifications** has a few simple configurations.
Instructions on how to use them in your own application are explained below.

| Configuration | Type | Explanation | Mandatory
| ------------- | ---- | ----------- | -------  |
| **bot_message** | *String* | The message Discord will show over the git activity summary. | No
| **bot_username** | *String* | The username of the bot sending Discord message. | No
| **bot_avatar** | *String* | The avatar of the bot sending Discord message. | No
| **bot_color** | *String* | The left bar color of messages sent on Discord (HEX, 6 chars, with no **#** symbol. Example: *E62B5A*). | No
| **secure_token** | *String* | String used as matching constraint for security reasons (*only on Gitlab*) | No

#### Initialization

Define your route (be sure using **POST** method), pair your controller and then import **PHP Discord GIT Notifications** class at the top of your file.

```php
use ArsDigitalia\Repository;
```

Once the class is imported, you can **initialize** the object:

```php
$repository = new Repository();
```

After that you can proceed declaring your **webhooks**:

```php
$repository->addWebhooks([
    // Example for GitHub
    '{github_hook_uuid}' => 'https://discord.com/api/webhooks/{channel_id}/{webhook_id}',
    // Example for Bitbucket
    '{bitbucket_webhook_uuid}' => 'https://discord.com/api/webhooks/{channel_id}/{webhook_id}',
    // Example for Gitlab
    '{gitlab_project_id}' => 'https://discord.com/api/webhooks/{channel_id}/{webhook_id}',
]);
```

If you want to declare a specific **configuration** value (as referenced in [**configuration**](#configuration) section), do in this way:

```php
$repository->config->set('{configuration_key}', '{configuration_value}');
```

After that you can proceed parsing *request* and then sending message to the Discord matching webhook:

```php
$repository = $repository->parseRequest();
$repository->sendMessage();
```

#### How to create and/or retrieve your Github Webhook UUID
1. Login into your **Github** account.
2. Search and enter in your **Repository**.
3. Click on *Settings* and then on *Webhooks* in the left sidebar.
4. Create your webhook if you haven't already did by entering the complete **url** of this route and making sure that *Content type* is set to **application/json** and choose **Let me select individual events** if interested (only **Pushes** and **Pull requests** will be considered).
5. After that, click on **edit** and retrieve the Github webhook uuid from the url path.


#### How to create and/or retrieve your Bitbucket Webhook UUID
1. Login into your **Bitbucket** account.
2. Search and enter in your **Repository**.
3. Click on *Repository settings* and then on *Webhooks* under *Workflow* subcategory.
4. Create your webhook if you haven't already did by entering the complete **url** of this route and making sure that **Push** (under repository), **Created** (under pull request) and **Approved** (under pull request) checkboxes are **enabled**.
5. After that, click on **edit** or **view requests** (if your are not on those sections yet) call to actions and retrieve the bitbucket webhook uuid from the url path (make sure to not copy braces characters **%7B** and **%7D** which can limit uuid string).

#### How to create and/or retrieve your Gitlab Project ID
1. Login into your **Gitlab** account.
2. Search and enter in your **Project**.
3. Click on *Settings* and then on *Webhooks*.
4. Create your webhook if you haven't already did by entering the complete **url** of this route and making sure that **Push events** checkbox is **enabled**.
5. After that, click on **General** on the left sidebar and retrieve the **Project ID** from the disabled input field.


#### How to create and/or retrieve your Discord channel Webhook URL
1. Make sure you have enough permissions to perform the task.
2. Identify your desired channel and move over that.
3. Click on the gear (*Edit channel*) and then on **Integrations**.
4. After that your can click on the "Create webhook" call to action, enter the required parameters and then copy the **Webhook URL**.


#### License

Apache License 2.0


**There's no place like 127.0.0.1!**
