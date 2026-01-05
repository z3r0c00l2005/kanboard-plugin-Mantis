<?php

namespace Kanboard\Plugin\Mantis\ExternalTask;

use Kanboard\Core\Base;
use Kanboard\Core\ExternalTask\ExternalTask;
use Kanboard\Core\ExternalTask\ExternalTaskProviderInterface;
use Kanboard\Core\ExternalTask\NotFoundException;
use Kanboard\Core\ExternalTask\ExternalTaskException;

#use SoapClient;

class MantisTaskProvider extends Base implements ExternalTaskProviderInterface
{
    private $client;

    #public function __construct($container)
    public function gogogo($uri)
    {
        $mantisUrl = $this->configModel->get('mantis_url','');
        $mantisApiToken = $this->configModel->get('mantis_api_token','');

        $this->client = new MantisRestClient($mantisUrl, $mantisApiToken);
    }

    /**
     * Fetch an external task
     *
     * $uri format expected by Kanboard plugin (depends on original implementation).
     * Here we assume uri contains the mantis issue id like "mantis:123"
     */
    public function fetch($uri,$projectID)
    {
        $this->gogogo($uri);	
        // parse issue id from uri
        if (preg_match('/(\d+)$/', $uri, $m)) {
            $issueId = intval($m[1]);
        } else {
            throw new ExternalTaskException('Invalid Mantis URI: ' . $uri);
        }

        try {
            $issue = $this->client->getIssue($issueId);
        } catch (\Exception $e) {
            throw new ExternalTaskException('Unable to fetch issue: ' . $e->getMessage());
        }

        if (empty($issue)) {
            throw new ExternalTaskException('Issue not found: ' . $issueId);
        }
	error_log('In fetch method');
	#error_log( print_r($issue,true));
        // Map Mantis fields to Kanboard expected fields
        $values = [
            'id' => $issue['id'], 
       	    'title' => isset($issue['summary']) ? $issue['summary'] : (isset($issue['field']['summary']) ? $issue['field']['summary'] : ''),
            'description' => isset($issue['description']) ? $issue['description'] : (isset($issue['field']['description']) ? $issue['field']['description'] : ''),
            'reference' => $issue['id'],
            'external_provider' => 'mantis',
            'date_submitted' => isset($issue['created_at']) ? strtotime($issue['created_at']) : 0,
            'last_updated' => isset($issue['updated_at']) ? strtotime($issue['updated_at']) : 0,
	    'reporter' => isset($issue['reporter']) ? $issue['reporter'] : '',
	    'handler' => isset($issue['handler']) ? $issue['handler'] : '',
	    'status' => isset($issue['status']) ? $issue['status'] : '',
	    'tags' => isset($issue['tags']) ? $issue['tags'] : '',
	    'project' => isset($issue['project']) ? $issue['project'] : '',
        ];
	error_log( print_r($values['tags'], true) );	
	return new MantisTask($uri, $values);
    }

    public function getName()
    {
        return 'Mantis';
    }

    public function getIcon()
    {
        return '<i class="fa fa-bug fa-fw"></i>';
    }

    public function getMenuAddLabel()
    {
        return t('Add a new Mantis issue');
    }

    public function save($uri, array $formValues, array &$formErrors)
    {
        return true;
    }

    public function getImportFormTemplate(array $values = [])
    {
	return 'Mantis:task/import';
    }

    public function getCreationFormTemplate(array $values = [])
    {
	return 'Mantis:task/creation';
    }

    public function getModificationFormTemplate(array $values = [])
    {
	return 'Mantis:task/modification';
    }

    public function getViewTemplate(array $values = [])
    {
	return 'Mantis:task/view';
    }

    public function buildTaskUri(array $formValues)
    {
        return $this->getBaseUrl() . '/view.php?id=' . $formValues['id'];
    }

    protected function getBaseUrl()
    {
	return $this->configModel->get('mantis_url');
	#return 'http://mantisbt/';
    }

}
