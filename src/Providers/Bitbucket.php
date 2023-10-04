<?php

namespace ArsDigitalia\Providers;

use Throwable;
use Exception;
use ArsDigitalia\Payload;
use ArsDigitalia\Repository;
use ArsDigitalia\ActionType;
use ArsDigitalia\DiscordMessage;
use ArsDigitalia\Exceptions\NotProvidedException;
use ArsDigitalia\Exceptions\ParsingPayloadException;

class Bitbucket extends Repository {

    function __construct() {
        parent::__construct();
    }

    function parseRequest() : Repository {
        $this->setActionType();
        $this->checkHookUUID();
        $this->config->set('repository_name', 'Bitbucket');
        $this->config->set('repository_image', 'https://wac-cdn.atlassian.com/assets/img/favicons/atlassian/favicon.png');
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
                $this->actionType = ActionType::PUSH();
                break;
            case 'pullrequest:created':
                $this->actionType = ActionType::PULL_REQUEST_CREATED();
                break;
            case 'pullrequest:approved':
                $this->actionType = ActionType::PULL_REQUEST_APPROVED();
                break;
            default:
                throw new NotProvidedException("Unhandled case for Bitbucket repository: open a Github issue for particular requests");
        }
    }

    function getFields() : array {
        switch ($this->actionType) {
            case 'PUSH':
                return $this->getPushMessage();
            case 'PULL_REQUEST_CREATED':
                return $this->getPullrequestMessage(ActionType::PULL_REQUEST_CREATED);
            case 'PULL_REQUEST_APPROVED':
                return $this->getPullrequestMessage(ActionType::PULL_REQUEST_APPROVED);
            default:
                throw new NotProvidedException("Unhandled case for Bitbucket repository: open a Github issue for particular requests");
        }
    }

    function getPushMessage() : array {
        $push = $this->payload['push'];
        $repository = $this->payload['repository'];
        $change = end($push['changes']);
        $commit = end($change['commits']);
        $payload = new Payload(
            'Successful execution',
            ActionType::PUSH,
            $commit['author']['raw'],
            substr($commit['hash'], 0, 7),
		    $commit['message'],
            $repository['full_name'],
            $change['new']['name'],
            $commit['links']['html']['href']
        );
        return $payload->toArray();
    }

    function getPullrequestMessage($actionType) : array {
        $pull_request = $this->payload['pullrequest'];
        $destination = $pull_request['destination'];
        $commit = $destination['commit'];
        $repository = $this->payload['repository'];
        $payload = new Payload(
            'Successful execution',
            $actionType,
            $pull_request['author']['display_name'],
            substr($commit['hash'], 0, 7),
            $pull_request['title'],
            $repository['full_name'],
            $destination['branch']['name'],
            $pull_request['links']['html']['href']
        );
        return $payload->toArray();
    }

}