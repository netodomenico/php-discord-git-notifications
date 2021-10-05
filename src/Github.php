<?php

namespace ArsDigitalia;

use Throwable;
use Exception;
use ArsDigitalia\ActionType;
use ArsDigitalia\Payload;
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
            // case 'pullrequest:created':
            //     $this->actionType = ActionType::PULL_REQUEST_CREATED();
            //     break;
            // case 'pullrequest:approved':
            //     $this->actionType = ActionType::PULL_REQUEST_APPROVED();
            //     break;
            default:
                throw new NotProvidedException("Unhandled case for Github repository: open a Github issue for particular requests");
        }
    }

    function getFields() : array {
        switch ($this->actionType) {
            case 'PUSH':
                return $this->getPushMessage();
            // case 'PULL_REQUEST_CREATED':
            //     return $this->getPullrequestCreatedMessage();
            // case 'PULL_REQUEST_UPDATED':
            //     return $this->getPullrequestApprovedMessage();
            default:
                throw new NotProvidedException("Unhandled case for Github repository: open a Github issue for particular requests");
        }
    }

    function getPushMessage() : array {
        $repository = $this->payload['repository'];
        $commit = $this->payload['commits'][0];
        $author = $commit['author'];
        $actionType = $this->actionType;
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

}
