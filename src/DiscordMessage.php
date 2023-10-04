<?php

namespace ArsDigitalia;

class DiscordMessage {

    var $response = null;
    var $config = [];

    public function __construct($fields, $config) {
        $this->config = $config;
        if(str_contains($fields['type'], 'Issue')) {
            $response = $this->getIssueFields($fields);
        } else if(str_contains($fields['type'], 'Comment')) {
            $response = $this->getCommentFields($fields);
        } else {
            $response = $this->getPushFields($fields);
        }
        $this->response = [
            "content" => $this->config->get('bot_message', 'I\'m working on our git repository: checkout my updates!'),
            "username" => $this->config->get('bot_username', 'Ars Digitalia'),
            "avatar_url" => $this->config->get('bot_avatar', 'https://www.arsdigitalia.net/wp-content/uploads/2017/12/cropped-Mobile-180x180.png'),
            "tts" => false,
            "embeds" => [
                [
                    "title" => "DETAILS",
                    "type" => "rich",
                    "url" => $fields['link'],
                    "color" => hexdec($this->config->get('bot_color', 'E62B5A')),
                    "footer" => [
                        "text" => $this->config->get('repository_name'),
                        "icon_url" => $this->config->get('repository_image'),
                    ],
                    "fields" => $response,
                ],
            ]
        ];
    }

    function getPushFields($fields) {
        return [
            [
                "name" => "Status",
                "value" => $fields['status'],
                "inline" => false
            ],
            [
                "name" => "Activity type",
                "value" => $fields['type'],
                "inline" => false
            ],
            [
                "name" => "Author",
                "value" => $fields['author'],
                "inline" => false
            ],
            [
                "name" => "Commit",
                "value" => $fields['commit'],
                "inline" => false
            ],
            [
                "name" => "Message",
                "value" => $fields['message'],
                "inline" => false
            ],
            [
                "name" => "Project",
                "value" => $fields['project'],
                "inline" => false
            ],
            [
                "name" => "Branch",
                "value" => $fields['branch'],
                "inline" => false
            ]
        ];
    }

    function getIssueFields($fields) {
        return [
            [
                "name" => "Status",
                "value" => $fields['status'],
                "inline" => false
            ],
            [
                "name" => "Activity type",
                "value" => $fields['type'],
                "inline" => false
            ],
            [
                "name" => "Author",
                "value" => $fields['author'],
                "inline" => false
            ],
            [
                "name" => "Title",
                "value" => $fields['commit'],
                "inline" => false
            ],
            [
                "name" => "Description",
                "value" => $fields['message'],
                "inline" => false
            ],
            [
                "name" => "Labels",
                "value" => $fields['project'],
                "inline" => false
            ],
            [
                "name" => "Assignees",
                "value" => $fields['branch'],
                "inline" => false
            ]
        ];
    }

    function getCommentFields($fields) {
        return [
            [
                "name" => "Status",
                "value" => $fields['status'],
                "inline" => false
            ],
            [
                "name" => "Activity type",
                "value" => $fields['type'],
                "inline" => false
            ],
            [
                "name" => "Author",
                "value" => $fields['author'],
                "inline" => false
            ],
            [
                "name" => "Issue title",
                "value" => $fields['commit'],
                "inline" => false
            ],
            [
                "name" => "Message",
                "value" => $fields['message'],
                "inline" => false
            ],
            [
                "name" => "Labels",
                "value" => $fields['project'],
                "inline" => false
            ],
            [
                "name" => "Assignees",
                "value" => $fields['branch'],
                "inline" => false
            ]
        ];
    }

    function toJson() : string {
        return json_encode($this->response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

}
