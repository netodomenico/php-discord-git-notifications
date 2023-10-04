<?php

namespace ArsDigitalia\Providers;

use Throwable;
use Exception;
use ArsDigitalia\Payload;
use ArsDigitalia\Repository;
use ArsDigitalia\ActionType;
use ArsDigitalia\DiscordMessage;
use ArsDigitalia\Exceptions\NotAuthorizedException;
use ArsDigitalia\Exceptions\NotProvidedException;
use ArsDigitalia\Exceptions\ParsingPayloadException;

class Gitlab extends Repository {

    function __construct() {
        parent::__construct();
    }

    function parseRequest() : Repository {
        $this->checkSecureToken();
        $this->setActionType();
        $this->config->set('repository_name', 'Gitlab');
        $this->config->set('repository_image', 'https://about.gitlab.com/nuxt-images/ico/favicon-192x192.png');
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
            case 'Issue Hook':
                switch ($this->payload['object_attributes']['state']) {
                    case 'opened':
                        $this->actionType = ActionType::ISSUE_OPENED();
                        break;
                    case 'closed':
                        $this->actionType = ActionType::ISSUE_CLOSED();
                        break;
                    default:
                        throw new NotProvidedException("Unhandled case for issue state: contact administrator for support");
                }
                break;
            
            case 'Note Hook':
                $this->actionType = ActionType::COMMENT_CREATED();
                break;
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
            case 'ISSUE_OPENED':
                return $this->getIssueMessage(ActionType::ISSUE_OPENED);
            case 'ISSUE_CLOSED':
                return $this->getIssueMessage(ActionType::ISSUE_CLOSED);
            case 'COMMENT_CREATED':
                return $this->getCommentMessage(ActionType::COMMENT_CREATED);
            default:
                throw new NotProvidedException("Unhandled case for repository: open a Github issue for particular requests");
        }
    }

    function getPushMessage() : array {
        $project = $this->payload['project'];
        $commit = end($this->payload['commits']);
        $author = $this->payload['user_name'] . (filter_var($this->payload['user_email'], FILTER_VALIDATE_EMAIL) ? ' <' . $this->payload['user_email'] . '>' : '');
        $payload = new Payload(
            'Successful execution',
            $this->payload['user_name'] == $commit['author']['name'] ? 'Push' : 'Merge by ' . $commit['author']['name'],
            $author,
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

    function getIssueMessage($actionType) : array {
        $user = $this->payload['user'];
        $assignees = collect($this->payload['assignees'] ?? [])->transform(function($assignee) {
            return $assignee['name'];
        })->implode(', ');
        $labels = collect($this->payload['labels'] ?? [])->transform(function($label) {
            return $label['title'];
        })->implode(', ');
        $attributes = $this->payload['object_attributes'];
        $payload = new Payload(
            'Issue update',
            $actionType,
            $user['name'] . ' <' . $user['email'] . '>',
            $attributes['title'],
            $attributes['description'],
            $labels ?? 'Uncategorized',
            $assignees != '' ? $assignees : 'Unassigned',
            $attributes['url']
        );
        return $payload->toArray();
    }

    function getCommentMessage($actionType) : array {
        $user = $this->payload['user'];
        $issue = $this->payload['issue'];
        $assignees = collect($issue['assignees'] ?? [])->transform(function($assignee) {
            return $assignee['name'];
        })->implode(', ');
        $labels = collect($issue['labels'] ?? [])->transform(function($label) {
            return $label['title'];
        })->implode(', ');
        $attributes = $this->payload['object_attributes'];
        $payload = new Payload(
            'Issue update',
            $actionType,
            $user['name'] . ' <' . $user['email'] . '>',
            $issue['title'],
            $attributes['note'],
            $labels ?? 'Uncategorized',
            $assignees != '' ? $assignees : 'Unassigned',
            $attributes['url']
        );
        return $payload->toArray();
    }

}
