<?php

namespace App\Frontend\Modules\ThermostatPlanif;

use Entity\ThermostatPlanif;
use FormBuilder\ThermostatPlanifFormBuilder;
use FormBuilder\ThermostatPlanifNameFormBuilder;
use Materialize\FlatButton;
use Materialize\FloatingActionButton;
use Materialize\Link;
use Materialize\WidgetFactory;
use OCFram\BackController;
use OCFram\DateFactory;
use OCFram\FormHandler;
use OCFram\HTTPRequest;

/**
 * Class ThermostatPlanifController
 * @package App\Frontend\Modules\ThermostatPlanif
 */
class ThermostatPlanifController extends BackController
{
    /**
     * @param HTTPRequest $request
     */
    public function executeIndex(HTTPRequest $request)
    {
        $this->page->addVar('title', 'Gestion du Planning');

        $manager = $this->managers->getManagerOf('ThermostatPlanif');
        $thermostatPlanningsContainer = $manager->getListArray();

        $cards = [];

        foreach ($thermostatPlanningsContainer as $thermostatPlannings) {

            $thermostatDatas = [];
            foreach ($thermostatPlannings as $thermostatPlanningObj) {
                $thermostatPlanning = json_decode(json_encode($thermostatPlanningObj), true);

                //DATA PREPARE FOR TABLE
                $hideColumns = ['id', 'nomid', 'nom', 'modeid', 'defaultModeid'];
                $thermostatPlanning["jour"] = DateFactory::toStrDay($thermostatPlanning['jour']);
                $thermostatPlanning["mode"] = $thermostatPlanning["mode"]["nom"];
                $thermostatPlanning["defaultMode"] = $thermostatPlanning["defaultMode"]["nom"];
                $linkEdit = new Link('', "../activapi.fr/thermostat-planif-edit-" . $thermostatPlanning["id"], 'edit', 'primaryTextColor');
                $thermostatPlanning["editer"] = $linkEdit->getHtmlForTable();
                $domId = $thermostatPlanning["nom"]["nom"];
                $thermostatDatas[] = $thermostatPlanning;
            }

            $table = WidgetFactory::makeTable($domId, $thermostatDatas, true, $hideColumns);

            $cardTitle = 'Thermostat : Planning  ' . $domId;
            $linkDelete = new Link('Supprimer ce Planning', "../activapi.fr/thermostat-planif-delete-" . $thermostatPlanning["nomid"], 'delete', 'secondaryTextColor');

            $cardContent = $linkDelete->getHtml();
            $cardContent .= $table->getHtml();

            $card = WidgetFactory::makeCard($domId, $cardTitle);
            $card->addContent($cardContent);

            $cards[] = $card;
        }

        $addPlanifFab = new FloatingActionButton([
            'id' => "addPlanifFab",
            'fixed' => true,
            'icon' => "add",
            'href' => "../activapi.fr/thermostat-planif-add"
        ]);

        $this->page->addVar('cards', $cards);
        $this->page->addVar('addPlanifFab', $addPlanifFab);
    }

    /**
     * @param HTTPRequest $request
     */
    function executeAdd(HTTPRequest $request)
    {
        $manager = $this->managers->getManagerOf('ThermostatPlanif');
        $domId = 'Ajout';
        $cardTitle = 'Thermostat : Planning  ' . $domId;
        $message = '';
        $name = null;

        if ($request->method() == 'POST') {
            if ($request->postExists('nom')) {
                $name = $request->postData('nom');
            }

            if (!is_null($name)) {
                $result = $manager->addPlanifTable($name);
                if ($result > 0) {
                    $message = '<p class="flow-text">OK</p>';
                } else {
                    $message = "Ce nom existe déjà!";
                }
            } else {
                $message = "Le nom est vide";
            }
        }

        $item = new ThermostatPlanif(['nom' => $name]);
        $fb = new ThermostatPlanifNameFormBuilder($item);
        $fb->build();
        $form = $fb->form();

        $fh = new FormHandler($form, $manager, $request);

        if ($fh->process()) {
            $this->app->httpResponse()->redirect('../activapi.fr/thermostat-planif');
        }
        $card = WidgetFactory::makeCard($domId, $cardTitle);
        $card->addContent($message);
        $card->addContent($this->editFormView($form));

        $this->page->addVar('card', $card);
    }

    /**
     * @param HTTPRequest $request
     */
    public function executeEdit(HTTPRequest $request)
    {
        $manager = $this->managers->getManagerOf('ThermostatPlanif');
        $modes = $manager->getModes();

        if ($request->method() == 'POST') {

            $item = new ThermostatPlanif([
                'jour' => $request->postData('jour'),
                'modeid' => $request->postData('modeid'),
                'defaultModeid' => $request->postData('defaultModeid'),
                'heure1Start' => $request->postData('heure1Start'),
                'heure1Stop' => $request->postData('heure1Stop'),
                'heure2Start' => $request->postData('heure2Start'),
                'heure2Stop' => $request->postData('heure2Stop'),
                'nomid' => $request->postData('nomid')
            ]);

            if ($request->getExists('id')) {
                $id = $request->getData('id');
                $item->setId($id);
            }

        } else {
            if ($request->getExists('id')) {

                $id = $request->getData("id");
                $item = $manager->getUnique($id);
            }
        }
        $cards = [];

        $domId = 'Edition';
        $item->modes = $modes;

        $tpfb = new ThermostatPlanifFormBuilder($item);
        $tpfb->build();
        $form = $tpfb->form();

        $fh = new FormHandler($form, $manager, $request);

        if ($fh->process()) {
            $this->app->httpResponse()->redirect('../activapi.fr/thermostat-planif');
        }

        $link = new Link("Edition",
            "../activapi.fr/thermostat-planif",
            "arrow_back",
            "white-text",
            "white-text");

        $cardTitle = $link->getHtml();

        $card = WidgetFactory::makeCard($domId, $cardTitle);
        $card->addContent($this->editFormView($form));
        $cards[] = $card;

        $this->page->addVar('title', 'Edition du Planning');
        $this->page->addVar('cards', $cards);

    }

    /**
     * @param HTTPRequest $request
     */
    public function executeDelete(HTTPRequest $request)
    {
        $manager = $this->managers->getManagerOf('ThermostatPlanif');

        $domId = 'Suppression';
        $nom = '';
        if ($request->method() == 'POST') {
            if ($request->getExists('id')) {
                $id = $request->getData('id');
                $manager->delete($id);
                $this->app->httpResponse()->redirect('../activapi.fr/thermostat-planif');
            }
        } else {
            if ($request->getExists('id')) {
                $id = $request->getData('id');
                $nom = $manager->getNom($id);
            }
        }

        $link = new Link($domId,
            "../activapi.fr/thermostat-planif",
            "arrow_back",
            "white-text",
            "white-text");

        $cardTitle = $link->getHtml();

        $card = WidgetFactory::makeCard($domId, $cardTitle);
        $card->addContent($this->deleteFormView());

        $this->page->addVar('title', 'Suppression du Planning');
        $this->page->addVar('card', $card);
    }

    /**
     * @return false|string
     */
    public function deleteFormView()
    {
        return $this->getBlock(BLOCK . '/deleteFormView.phtml');
    }

    /**
     * @param $form
     * @return false|string
     */
    public function editFormView($form)
    {
        return $this->getBlock(BLOCK . '/editFormView.phtml', $form);
    }
}
