<?php

namespace ArsDigitalia;

use Exception;
use ArsDigitalia\Actions;
use ArsDigitalia\DiscordMessage;
use ArsDigitalia\RepositoryConfig;
use ArsDigitalia\Exceptions\NotFoundException;
use ArsDigitalia\Exceptions\NotProvidedException;
use ArsDigitalia\Exceptions\NotAuthorizedException;

class Repository {

    var $webhooks = [];
    var $headers = null;
    var $payload = null;
    var $uuid = null;
    var $action_type = null;
    var $config = null;
    var $message = null;
    var $providerName = null;

    function __construct(array $webhooks = []) {
        $this->addWebhooks($webhooks);
        $this->config = new RepositoryConfig();
    }

    function addWebhooks($webhooks) : void {
        foreach ($webhooks as $id => $webhook) {
            $this->webhooks[] = new Webhook($id, $webhook);
        }
    }

    function parseRequest() : Repository {
        $this->headers = getallheaders();
        $this->payload = json_decode(file_get_contents('php://input'), true);
        if(array_key_exists('X-Event-Key', $this->headers)) {
            $this->providerName = RepositoryProvider::BITBUCKET;
        } else if(array_key_exists('X-Gitlab-Event', $this->headers)) {
            $this->providerName = RepositoryProvider::GITLAB;
        } else if(is_null($this->providerName)) {
            throw new NotProvidedException("Repository provider not found: make sure you are doing the right process.");
        }
        $object = $this->castAs('ArsDigitalia\\' . $this->providerName);
        $object->parseRequest();
        return $object;
    }

    function getWebhookById() : string {
        $key = array_search($this->uuid, array_column($this->webhooks, 'id'));
        if(false === $key) {
            throw new NotFoundException("Webhook (" . $this->uuid . ") not found: make sure you have entered it during initialization or that you have specified the correct Webhook ID.");
        }
        return $this->webhooks[$key]->webhook;
    }

    function sendMessage() : void {
        $ch = curl_init($this->getWebhookById());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->message->toJson());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    public function castAs($repositoryProviderClass) : Repository {
        $object = new $repositoryProviderClass;
        foreach (get_object_vars($this) as $key => $name) {
            $object->$key = $name;
        }
        return $object;
    }

}
