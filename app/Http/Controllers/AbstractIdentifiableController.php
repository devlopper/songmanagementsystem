<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\UserInterface\Page\Page;
use App\Model\Constant;

abstract class AbstractIdentifiableController extends \App\Http\Controllers\AbstractController{

  protected function getMany(Request $request){
      $classInfos = \App\Model\Identifiable\IdentifiableClass::getByClassName($this->getIdentifiableClassName());
      $business = $this->getBusinessInstance();
      $start = intval($request->input('start'));
      $paginator = new \App\Model\Utils\Pagination($start,intval($request->input('length')));
      $identifiables = $business->findAllUsingPagination($paginator);
      $i = 0;
      $dtoClass = $this->getDtoClass();
      $data = array();
      foreach ($identifiables as $identifiable) {
        $dto = new $dtoClass;
        $dto->setIdentifiable($identifiable);
        $commandCollection = new \App\Model\UserInterface\Command\Collection();
        $commandCollection->createCommand("message.command.read",route('show'.$classInfos->simpleClassName.'ReadPage'
          ,[$dto->identifier]),"glyphicon glyphicon-eye-open","btn-primary");
        $commandCollection->createCommand("message.command.update",route('show'.$classInfos->simpleClassName.'UpdatePage',[$dto->identifier])
          ,"glyphicon glyphicon-edit","btn-warning");
        $commandCollection->createCommand("message.command.delete",route('show'.$classInfos->simpleClassName.'DeletePage',[$dto->identifier])
          ,"glyphicon glyphicon-trash","btn-danger");

        $data[$i++] = $this->getAsArray($request,$start,$i,$dto,$commandCollection->getHtml());

    }
    return ['draw'=> intval($request->input('draw'))+1,'recordsTotal'=> $business->countAll(),'recordsFiltered'=>$business->countAllUsingPagination($paginator)
      ,'data'=> $data ];
  }

  protected function getAsArray(Request $request,$start,$i,$dto,$commands){
    $entry = array();
    $entry["DT_RowId"] = $dto->identifier;
    $entry["DT_RowData"] = ["pkey"=>$dto->identifier];
    $entry[] = $start+$i;
    $entry[] = $dto->code;
    $entry[] = $dto->name;
    $entry = $this->processDtoArray($request,$dto,$entry);
    $entry[] = $commands;
    return $entry;
  }

  protected function processDtoArray(Request $request,$dto,$entry){
    return $entry;
  }

  /**/

  public function showListPage(Request $request){
    $classInfos = \App\Model\Identifiable\IdentifiableClass::getByClassName($this->getIdentifiableClassName());
    $business = $this->getBusinessInstance();
    $page = $this->instanciatePage($request,"List of",$classInfos->label);
    $table = $this->instanciateTable($request);
    $table = $this->addTableColumns($request,$table);
    $page->view = $classInfos->identifier.'/list';
    $page->addTable($table);
    return $this->gotoPage($page);
  }

  protected function addTableColumns(Request $request,$table){
    $table->addColumn("code");
    $table->addColumn("name");
    $table = $this->addSpecificTableColumns($request,$table);
    return $table;
  }

  protected function addSpecificTableColumns(Request $request,$table){
    return $table;
  }

  /**/

  public function createCrudOnePage(Request $request,$crud,$identifiable){
    $page = Page::createCrudOne($crud,$identifiable);
    $controlCollection = $this->buildFormControlCollection($request,$page->forms[0]);
    return $page;
  }

  public function showCrudOnePage(Request $request,$crud,$identifier){
    $business = $this->getBusinessInstance();
    $identifiable = $identifier == null ? $business->instanciateOne() : $business->find($identifier);
    $page = $this->createCrudOnePage($request,$crud,$identifiable);
    return $page->navigate();
  }

  public function showCreatePage(Request $request){
    return $this->showCrudOnePage($request,Constant::CRUD_CREATE,null);
  }

  public function showReadPage(Request $request,$identifier){
    return $this->showCrudOnePage($request,Constant::CRUD_READ,$identifier);
  }

  public function showUpdatePage(Request $request,$identifier){
    return $this->showCrudOnePage($request,Constant::CRUD_UPDATE,$identifier);
  }

  public function showDeletePage(Request $request,$identifier){
    return $this->showCrudOnePage($request,Constant::CRUD_DELETE,$identifier);
  }

  public function crudOne(Request $request){
    $this->validateRequest($request);
    $classInfos = \App\Model\Identifiable\IdentifiableClass::getByClassName($this->getIdentifiableClassName());
    $business = $this->getBusinessInstance();

    $identifier = $request->all()[Constant::FIELD_IDENTIFIER];
    if(empty($identifier)){
        $identifiable = $business->instanciateOne();
    }else{
        $identifiable = $business->find($identifier);
    }

    $form = $this->getFormInstance($identifiable,1);
    $form->setFromRequest($request);
    $form->writeToIdentifiable();
    $identifiable = $form->getIdentifiable();
    if(strcmp(Constant::CRUD_DELETE,$form->action)==0){
        $business->delete($identifiable);
    }else{
        $business->save($identifiable);
    }

    return redirect()->route('show'.$classInfos->simpleClassName.'ListPage');
  }

  protected function buildFormControlCollection(Request $request,$form){
    $controlCollection = $form->addControlCollection();
    $controlCollection->addInputText("action");
    $controlCollection->addInputText("identifier");
    $controlCollection->addInputText("code");
    $controlCollection->addInputText("name");
    return $controlCollection;
  }

  /**/

  protected abstract function getIdentifiableClassName();

  protected function getBusinessClass(){
    return \App\Utils::getIdentifiableBusinessClassName($this->getIdentifiableClassName());
  }

  protected function getBusinessInstance(){
    $businessClass = $this->getBusinessClass();
    $business = new $businessClass;
    return $business;
  }

  protected function getDtoClass(){
    return \App\Utils::getIdentifiableDtoClassName($this->getIdentifiableClassName());
  }

  protected function getDtoInstance($identifiable){
    $dto = new $dtoClass;
    $dto = new $dtoClass;
    $dto->setIdentifiable($identifiable);
    return $dto;
  }

  protected function getFormClass(){
    return \App\Utils::getIdentifiableFormClassName($this->getIdentifiableClassName());
  }

  protected function getFormInstance($identifiable,$editable){
    $formClass = $this->getFormClass();
    $form = new $formClass(["editable" => $editable,"action"=>1]);
    $form->setIdentifiable($identifiable);
    return $form;
  }

}
