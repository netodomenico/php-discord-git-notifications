<?php

namespace ArsDigitalia;

class Payload {

    var $status = null;
    var $type = null;
    var $author = null;
    var $commit = null;
    var $message = null;
    var $project = null;
    var $branch = null;
    var $link = null;

    function __construct(string $status, string $type, string $author, string $commit, string $message, string $project, string $branch, string $link) {
        $this->status = $status;
        $this->type = $type;
        $this->author = $author;
        $this->commit = $commit;
        $this->message = $message;
        $this->project = $project;
        $this->branch = $branch;
        $this->link = $link;
    }

    function toArray() : array {
        return [
            'status' => $this->status,
            'type' => $this->type,
            'author' => $this->author,
            'commit' => $this->commit,
            'message' => $this->message,
            'project' => $this->project,
            'branch' => $this->branch,
            'link' => $this->link,
        ];
    }

}