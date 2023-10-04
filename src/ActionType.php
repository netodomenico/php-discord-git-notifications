<?php

namespace ArsDigitalia;

class ActionType extends Enum {

    const PUSH = 'Push';
    const PULL_REQUEST_CREATED = 'Pull request (created)';
    const PULL_REQUEST_APPROVED = 'Pull request (approved)';
    const ISSUE_OPENED = 'Issue (opened)';
    const ISSUE_CLOSED = 'Issue (closed)';
    const COMMENT_CREATED = 'Comment';

}