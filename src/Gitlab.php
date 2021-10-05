<?php

namespace ArsDigitalia;

use Throwable;
use Exception;
use ArsDigitalia\Payload;
use ArsDigitalia\DiscordMessage;
use ArsDigitalia\Exceptions\NotAuthorizedException;
use ArsDigitalia\Exceptions\NotProvidedException;
use ArsDigitalia\Exceptions\ParsingPayloadException;

class Gitlab extends Repository {

    function __construct() {
        parent::__construct();
    }

    function parseRequest() : Gitlab {
        $this->checkSecureToken();
        $this->setActionType();
        $this->config->set('repository_name', 'Gitlab');
        $this->config->set('repository_image', 'https://gitlab.com/uploads/-/system/project/avatar/278964/logo-extra-whitespace.png');
        try {
            $this->uuid = $this->payload['project']['id'];
            $this->message = new DiscordMessage($this->getFields(), $this->config);
        } catch (Throwable $th) {
            throw new ParsingPayloadException("Error parsing webhook payload. Try again or open a Github issue.");
        }
        return $this;
    }

    function checkSecureToken() : void {
        $secure_token = $this->config->get('secure_token', null);
        if($secure_token != null) {
            if(!array_key_exists('X-Gitlab-Token', $this->headers)) {
                throw new Exception("X-Gitlab-Token header not provided. Make sure you are doing the right process.");
            }
            $gitlab_token = $this->headers['X-Gitlab-Token'];
            if($gitlab_token != $secure_token) {
                throw new NotAuthorizedException("Not authorized: X-Gitlab-Token doesn't match with the provided secure token.");
            }
        }
    }

    function checkEvent() : void {
        if(!array_key_exists('X-Gitlab-Event', $this->headers)) {
            throw new Exception("X-Gitlab-Event header not provided. Make sure you are doing the right process.");
        }
    }

    function setActionType() : void {
        $this->checkEvent();
        switch ($this->headers['X-Gitlab-Event']) {
            case 'Push Hook':
                $this->actionType = ActionType::PUSH();
                break;
            case 'Merge Request Hook':
                if($this->payload['object_attributes']['action'] == 'open') {
                    $this->actionType = ActionType::PULL_REQUEST_CREATED();
                    break;
                }
                if($this->payload['object_attributes']['action'] == 'approved') {
                    $this->actionType = ActionType::PULL_REQUEST_APPROVED();
                    break;
                }
            default:
                throw new NotProvidedException("Unhandled case for repository: open a Github issue for particular requests");
        }
    }

    public function getFields() : array {
        switch ($this->actionType) {
            case 'PUSH':
                return $this->getPushMessage();
            case 'PULL_REQUEST_CREATED':
                return $this->getPullrequestMessage(ActionType::PULL_REQUEST_CREATED);
            case 'PULL_REQUEST_APPROVED':
                return $this->getPullrequestMessage(ActionType::PULL_REQUEST_APPROVED);
            default:
                throw new NotProvidedException("Unhandled case for repository: open a Github issue for particular requests");
        }
    }

    function getPushMessage() : array {
        $project = $this->payload['project'];
        $commit = $this->payload['commits'][0];
        $author = $commit['author'];
        $payload = new Payload(
            'Successful execution',
            'Push',
            $author['name'] . ' <' . $author['email'] . '>',
            substr($this->payload['checkout_sha'], 0, 7),
            $commit['message'],
            $project['namespace'] . '/' . $project['name'],
            $this->payload['ref'],
            $project['web_url']
        );
        return $payload->toArray();
    }

    function getPullrequestMessage($actionType) : array {
        $project = $this->payload['project'];
        $user = $this->payload['user'];
        $attributes = $this->payload['object_attributes'];
        $commit = $attributes['last_commit'];
        $payload = new Payload(
            'Successful execution',
            $actionType,
            $user['name'] . ' <' . $user['email'] . '>',
            substr($commit['id'], 0, 7),
            $commit['message'],
            $project['namespace'] . '/' . $project['name'],
            $attributes['target_branch'],
            $commit['url']
        );
        return $payload->toArray();
    }

}
