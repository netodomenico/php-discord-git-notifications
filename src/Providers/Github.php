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

class Github extends Repository {

    function __construct() {
        parent::__construct();
    }

    function parseRequest() : Github {
        $this->setActionType();
        $this->checkHookUUID();
        $this->config->set('repository_name', 'Github');
        $this->config->set('repository_image', 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Octicons-mark-github.svg/2048px-Octicons-mark-github.svg.png');
        try {
            $this->uuid = $this->headers['X-GitHub-Hook-ID'];
            $this->message = new DiscordMessage($this->getFields(), $this->config);
        } catch (Throwable $th) {
            throw new ParsingPayloadException("Error parsing webhook payload. Try again or open a Github issue.");
        }
        return $this;
    }

    function checkHookUUID() : void {
        if(!array_key_exists('X-GitHub-Hook-ID', $this->headers)) {
            throw new Exception("X-GitHub-Hook-ID header not provided. Make sure you are doing the right process.");
        }
    }

    function checkEvent() : void {
        if(!array_key_exists('X-GitHub-Event', $this->headers)) {
            throw new Exception("X-GitHub-Event header not provided. Make sure you are doing the right process.");
        }
    }

    function setActionType() : void {
        $this->checkEvent();
        switch ($this->headers['X-GitHub-Event']) {
            case 'push':
                $this->actionType = ActionType::PUSH();
                break;
            case 'pull_request':
                if($this->payload['action'] == 'opened') {
                    $this->actionType = ActionType::PULL_REQUEST_CREATED();
                    break;
                }
                if($this->payload['action'] == 'closed') {
                    $this->actionType = ActionType::PULL_REQUEST_APPROVED();
                    break;
                }
            default:
                throw new NotProvidedException("Unhandled case for Github repository: open a Github issue for particular requests");
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
                throw new NotProvidedException("Unhandled case for Github repository: open a Github issue for particular requests");
        }
    }

    function getPushMessage() : array {
        $repository = $this->payload['repository'];
        $commit = $this->payload['commits'][0];
        $author = $commit['author'];
        $payload = new Payload(
            'Successful execution',
            ActionType::PUSH,
            $author['name'] . ' <' . $author['email'] . '>',
            substr($commit['id'], 0, 7),
		    $commit['message'],
            $repository['full_name'],
            $this->payload['ref'],
            $commit['url']
        );
        return $payload->toArray();
    }

    function getPullrequestMessage($actionType) : array {
        $repository = $this->payload['repository'];
        $pull_request = $this->payload['pull_request'];
        $sender = $this->payload['sender'];
        $payload = new Payload(
            'Successful execution',
            $actionType,
            $sender['login'],
            substr($pull_request['head']['sha'], 0, 7),
		    $pull_request['body'],
            $repository['full_name'],
            $pull_request['head']['ref'],
            $pull_request['html_url']
        );
        return $payload->toArray();
    }

}
