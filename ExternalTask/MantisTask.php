<?php

namespace Kanboard\Plugin\Mantis\ExternalTask;

use Kanboard\Core\ExternalTask\ExternalTaskInterface;

class MantisTask implements ExternalTaskInterface
{
    protected $uri;
    protected $issue;

    public function __construct($uri, $issue)
    {
        $this->uri = $uri;
        $this->issue = $issue;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getIssueId()
    {
	return isset($this->issue['id']) ? $this->issue['id'] : null;
    }

    public function getIssue()
    {
        return $this->issue;
    }

    public function getFormValues()
    {
	$title = sprintf(
            'MT %d %s',
            isset($this->issue['id']) ? $this->issue['id'] : 0,
            isset($this->issue['title']) ? $this->issue['title'] : ''
        );

        return [
            'title' => $title,
            'description' => isset($this->issue['description']) ? $this->issue['description'] : '',
            'reference' => $this->getUri(),
        ];        
    }
}
