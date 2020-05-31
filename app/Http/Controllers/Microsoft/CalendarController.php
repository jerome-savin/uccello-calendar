<?php

namespace Uccello\Calendar\Http\Controllers\Microsoft;

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use Uccello\Core\Http\Controllers\Core\Controller;
use Uccello\Core\Models\Domain;
use Uccello\Core\Models\Module;
use Uccello\Calendar\CalendarAccount;


class CalendarController extends Controller
{
    public function list(Domain $domain, Module $module, $accountId)
    {
        $graph = $this->initClient($accountId);

        $calendarList = $graph->createRequest('GET', '/me/calendars')
                        ->setReturnType(Model\Calendar::class)
                        ->execute();

        $calendars = [];

        foreach($calendarList as $calendarListEntry)
        {
            $calendar = new \StdClass;
            $calendar->name = $calendarListEntry->getName();
            $calendar->id = $calendarListEntry->getProperties()['id'];
            $calendar->service = 'microsoft';
            $calendar->color = $calendarListEntry->getProperties()['color'];
            $calendar->accountId = $accountId;
            $calendar->read_only = !boolval($calendarListEntry->getCanEdit());

            if($calendar->color=='auto')
                $calendar->color = '#03A9F4';

            $calendars[] = $calendar;
        }

        return $calendars;
    }

    public function create(Domain $domain, Module $module, $accountId)
    {
        $graph = $this->initClient($accountId);

        $parameters = new \StdClass;
        $parameters->Name = request('calendarName');

        $calendar = $graph->createRequest('POST', '/me/calendars')
                        ->attachBody($parameters)
                        ->setReturnType(Model\Calendar::class)
                        ->execute();
    }

    public function destroy(Domain $domain, Module $module, CalendarAccount $account, $calendarId)
    {
        $graph = $this->initClient($account->id);

        $calendar = $graph->createRequest('DELETE', '/me/calendars/'.$calendarId)
                        ->setReturnType(Model\Calendar::class)
                        ->execute();
    }

    public function getCategories(Domain $domain, Module $module, CalendarAccount $account)
    {
        $categories = collect();

        $graph = $this->initClient($account->id);
        $response = $graph->createRequest('GET', '/me/outlook/masterCategories')->execute();
        $body = $response->getBody();

        if ($body['value'] && is_array($body['value'])) {
            foreach ($body['value'] as $category) {
                $_category = new \stdClass;
                $_category->id = $category['id'];
                $_category->label = $category['displayName'];
                $_category->value = $category['displayName'];
                $_category->color = static::getColorByPreset($category['color']);

                $categories->push($_category);
            }
        }

        return $categories;
    }

    public static function getColorByPreset($presetName)
    {
        $colors = static::getColors();

        $index = str_replace('preset', '', $presetName);

        if (is_numeric($index) && $index < count($colors)) {
            return $colors[$index];
        }

        return "#FFFFFF";
    }

    public static function getColors()
    {
        return [
            '#D6252E',
            '#F06C15',
            '#FFCA4C',
            '#FFFE3D',
            '#4AB63F',
            '#40BD95',
            '#859A52',
            '#3267B8',
            '#613DB4',
            '#A34E78',
            '#C4CCDD',
            '#8C9CBD',
            '#C4C4C4',
            '#A5A5A5',
            '#1C1C1C',
            '#AF1E25',
            '#B14F0D',
            '#AB7B05',
            '#999400',
            '#35792B',
            '#2E7D64',
            '#5F6C3A',
            '#2A5191',
            '#50328F',
            '#82375F'
        ];
    }

    private function initClient($accountId)
    {
        $accountController = new AccountController();
        return $accountController->initClient($accountId);
    }
}