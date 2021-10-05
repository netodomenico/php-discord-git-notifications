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
                $this->action_type = ActionType::PUSH();
                break;
            // case 'pullrequest:created':
            //     $this->action_type = ActionType::PULL_REQUEST_CREATED();
            //     break;
            // case 'pullrequest:approved':
            //     $this->action_type = ActionType::PULL_REQUEST_APPROVED();
            //     break;
            default:
                throw new NotProvidedException("Unhandled case for repository: open a Github issue for particular requests");
        }
    }

    public function getFields() : array {
        switch ($this->action_type) {
            case 'PUSH':
                return $this->getPushMessage();
            // case 'PULL_REQUEST_CREATED':
            //     return $this->getPullrequestCreatedMessage();
            // case 'PULL_REQUEST_UPDATED':
            //     return $this->getPullrequestApprovedMessage();
            default:
                throw new NotProvidedException("Unhandled case for repository: open a Github issue for particular requests");
        }
    }

    function getPushMessage() : array {
        $project = $this->payload['project'];
        $repository = $this->payload['repository'];
        $commit = $this->payload['commits'][0];
        $payload = new Payload(
            'Successful execution',
            'Push',
            $commit['author']['name'] . ' <' . $commit['author']['email'] . '>',
            substr($this->payload['checkout_sha'], 0, 7),
            $commit['message'],
            $project['namespace'] . '/' . $project['name'],
            $this->payload['ref'],
            $project['web_url']
        );
        return $payload->toArray();
    }

}
