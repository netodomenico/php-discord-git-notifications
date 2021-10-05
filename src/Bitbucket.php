<?php

namespace ArsDigitalia;

use Throwable;
use Exception;
use ArsDigitalia\Payload;
use ArsDigitalia\DiscordMessage;
use ArsDigitalia\Exceptions\NotProvidedException;
use ArsDigitalia\Exceptions\ParsingPayloadException;

class Bitbucket extends Repository {

    function __construct() {
        parent::__construct();
    }

    function parseRequest() : Bitbucket {
        $this->setActionType();
        $this->checkHookUUID();
        $this->config->set('repository_name', 'Bitbucket');
        $this->config->set('repository_image', 'https://wac-cdn.atlassian.com/assets/img/favicons/bitbucket/apple-touch-icon.png');
        try {
            $this->uuid = $this->headers['X-Hook-UUID'];
            $this->message = new DiscordMessage($this->getFields(), $this->config);
        } catch (Throwable $th) {
            throw new ParsingPayloadException("Error parsing webhook payload. Try again or open a Github issue.");
        }
        return $this;
    }

    function checkHookUUID() : void {
        if(!array_key_exists('X-Hook-UUID', $this->headers)) {
            throw new Exception("X-Hook-UUID header not provided. Make sure you are doing the right process.");
        }
    }

    function checkEvent() : void {
        if(!array_key_exists('X-Event-Key', $this->headers)) {
            throw new Exception("X-Event-Key header not provided. Make sure you are doing the right process.");
        }
    }

    function setActionType() : void {
        $this->checkEvent();
        switch ($this->headers['X-Event-Key']) {
            case 'repo:push':
                $this->action_type = ActionType::PUSH();
                break;
            case 'pullrequest:created':
                $this->action_type = ActionType::PULL_REQUEST_CREATED();
                break;
            case 'pullrequest:approved':
                $this->action_type = ActionType::PULL_REQUEST_APPROVED();
                break;
            default:
                throw new NotProvidedException("Unhandled case for Bitbucket repository: open a Github issue for particular requests");
        }
    }

    function getFields() : array {
        switch ($this->action_type) {
            case 'PUSH':
                return $this->getPushMessage();
            case 'PULL_REQUEST_CREATED':
                return $this->getPullrequestCreatedMessage();
            case 'PULL_REQUEST_UPDATED':
                return $this->getPullrequestApprovedMessage();
            default:
                throw new NotProvidedException("Unhandled case for Bitbucket repository: open a Github issue for particular requests");
        }
    }

    function getPushMessage() : array {
        $push = $this->payload['push'];
        $repository = $this->payload['repository'];
        $change = $push['changes'][0];
        $commit = $change['commits'][0];
        $payload = new Payload(
            'Successful execution',
            'Push',
            $commit['author']['raw'],
            substr($commit['hash'], 0, 7),
		    $commit['message'],
            $repository['full_name'],
            $change['new']['name'],
            $commit['links']['html']['href']
        );
        return $payload->toArray();
    }

    function getPullrequestCreatedMessage() : array {
        $pull_request = $this->payload['pullrequest'];
        $destination = $pull_request['destination'];
        $commit = $destination['commit'];
        $repository = $this->payload['repository'];
        $payload = new Payload(
            'Successful execution',
            'Pull request created',
            $pull_request['author']['display_name'],
            substr($commit['hash'], 0, 7),
            $pull_request['title'],
            $repository['full_name'],
            $destination['branch']['name'],
            $pull_request['links']['html']['href']
        );
        return $payload->toArray();
    }

    function getPullrequestApprovedMessage() : array {
        $approval = $this->payload['approval'];
        $pull_request = $this->payload['pullrequest'];
        $destination = $pull_request['destination'];
        $commit = $destination['commit'];
        $repository = $this->payload['repository'];
        $payload = new Payload(
            'Successful execution',
            'Pull request appoved',
            $approval['user']['display_name'],
            substr($commit['hash'], 0, 7),
            $pull_request['title'],
            $repository['full_name'],
            $destination['branch']['name'],
            $pull_request['links']['html']['href']
        );
        return $payload->toArray();
    }


}
