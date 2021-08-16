<?php

    class MenusController extends AppController {
        
        var $name = 'Menu';
        var $layout = 'admin';
        var $uses = array('MenuRodape', 'SubMenuRodape', 'Pagina');
        
        function beforeFilter(){
            $this->Auth->allow('montarRodape');
        } 
        
        public function admin_rodape() {
            if( !$this->request->is('ajax')){
                $this->request->params['named']['page'] = 1;
            }
            
            if(isset($this->request->query['busca'])){  
                $opcoes['conditions']['and']['MenuRodape.descricao ilike'] = "%{$this->request->query['busca']}%";
            }
            
            $opcoes['limit'] = 20;
            $opcoes['order'] = array('MenuRodape.ordem' => 'DESC', 'MenuRodape.codigo' => 'ASC');
            
            $this->paginate = $opcoes;
            $this->MenuRodape->recursive = 0;
            $this->set('menusRodape', $this->paginate());
        }
        
        function admin_addRodape() {
            if (!empty($this->data)) {
                try{
                    // Seleciona a última ordem cadastrada e adiciona na próxima
                    $total = $this->MenuRodape->query('SELECT ordem as Total__ordem FROM menu_rodape ORDER BY ordem DESC LIMIT 1');
                    if(!empty($total)){
                        $this->request->data['MenuRodape']['ordem'] = $total[0]['total']['ordem'] + 1;
                    } else {
                        $this->request->data['MenuRodape']['ordem'] = 1;
                    }
                    
                    $this->MenuRodape->save($this->data);
                    $this->Session->setFlash(__('O menu foi cadastrado com sucesso.'), 'sucesso');
                    $this->redirect(array('action'=>'admin_rodape'));
                } catch (Exception $e) {
                    $this->Session->setFlash(__('Erro ao cadastrar o menu.'), 'erro');
                }
            }
        }
        
        function admin_editRodape($codigo = null){
            if (!empty($this->data)) {
                if($this->MenuRodape->save($this->data)){
                    $this->Session->setFlash(__('O menu foi editado com sucesso.'), 'sucesso');
                    $this->redirect(array('action'=>'admin_rodape'));
                } else {
                    $this->Session->setFlash(__('Erro ao editar o menu.'), 'erro');
                }
            }
            
            $this->MenuRodape = $this->MenuRodape->find('first', array('conditions' => array('MenuRodape.codigo' => $codigo)));
            $this->request->data = $this->MenuRodape;
            
        }
        
        function admin_rodapeUp($codigo = null){
            
            $rodapeUp = $this->MenuRodape->find('first', array('conditions' => array('MenuRodape.codigo' => $codigo)));
            $rodapeDown = $this->MenuRodape->find('first', 
                    array('conditions' => array('MenuRodape.ordem ' =>  ($rodapeUp['MenuRodape']['ordem'] + 1) )));
            
            if(!empty($rodapeDown['MenuRodape']['codigo'])){
                $dataSource = $this->Noticia->getDataSource();
                try{
                    $dataSource->begin();
                    $rodapeUp['MenuRodape']['ordem'] = $rodapeUp['MenuRodape']['ordem'] + 1;
                    $rodapeDown['MenuRodape']['ordem'] = $rodapeDown['MenuRodape']['ordem'] - 1;
                    $this->MenuRodape->save($rodapeUp);
                    $this->MenuRodape->save($rodapeDown);
                    $dataSource->commit();
                    $this->Session->setFlash(__('O menu foi movido com sucesso.'), 'sucesso');
                } catch (Exception $e){
                   $dataSource->rollback();
                   $this->Session->setFlash(__('Erro ao cadastrar a notícia! -> ' . $e->getMessage()), 'erro'); 
                }
            }            
            
            $this->redirect(array('action'=>'admin_rodape'));
        }
        
        function admin_rodapeDown($codigo = null){

            $rodapeDown = $this->MenuRodape->find('first', array('conditions' => array('MenuRodape.codigo' => $codigo)));
            $rodapeUp = $this->MenuRodape->find('first', 
                    array('conditions' => array('MenuRodape.ordem ' =>  ($rodapeDown['MenuRodape']['ordem'] - 1) )));
            
            if(!empty($rodapeUp['MenuRodape']['codigo'])){
                $dataSource = $this->MenuRodape->getDataSource();
                try{
                    $dataSource->begin();
                    $rodapeDown['MenuRodape']['ordem'] = $rodapeDown['MenuRodape']['ordem'] - 1;
                    $rodapeUp['MenuRodape']['ordem'] = $rodapeUp['MenuRodape']['ordem'] + 1;
                    $this->MenuRodape->save($rodapeDown);
                    $this->MenuRodape->save($rodapeUp);
                    $dataSource->commit();
                    $this->Session->setFlash(__('O menu foi movido com sucesso.'), 'sucesso');
                } catch (Exception $e){
                   $dataSource->rollback();
                   $this->Session->setFlash(__('Erro ao cadastrar a notícia! -> ' . $e->getMessage()), 'erro'); 
                }
            }
            
            $this->redirect(array('action'=>'admin_rodape'));
        }
        
        function admin_subRodape($codigo = null){
            
            // TODO - Listar o menu principal e os sub-menus....
            $this->set('menuRodape', $this->MenuRodape->find('first', array('conditions' => array('MenuRodape.codigo' => $codigo))));
            $this->set('subMenuRodape', $this->SubMenuRodape->find('all', 
                    array('conditions' => array('SubMenuRodape.codigo_menu_rodape' => $codigo),
                          'order' => array('SubMenuRodape.ordem' => 'DESC', 'SubMenuRodape.codigo' => 'ASC')
                        )
                    ));
            
        }
        
        public function admin_deleteRodape($codigo){
            $this->SubMenuRodape->deleteAll(array('SubMenuRodape.codigo_menu_rodape' => $codigo));
            $this->MenuRodape->delete($codigo);
            $this->Session->setFlash(__('Rodapé apagado com sucesso.'), 'sucesso');
            $this->redirect(array('action'=>'admin_rodape'));
        }
        
        public function admin_deleteSubRodape($codigo){
            $this->SubMenuRodape->delete($codigo);
            $this->Session->setFlash(__('Rodapé apagado com sucesso.'), 'sucesso');
            $this->redirect(array('action'=>'admin_rodape'));
        }
        
        function admin_addSubRodape(){
            
            if(!empty($this->data)){
                try {
                    $total = $this->SubMenuRodape->query('SELECT ordem as Total__ordem FROM sub_menu_rodape WHERE codigo_menu_rodape = '. $this->data['SubMenuRodape']['codigo_menu_rodape'] . ' ORDER BY ordem DESC LIMIT 1');
                    if(!empty($total)){
                        $this->request->data['SubMenuRodape']['ordem'] = $total[0]['total']['ordem'] + 1;
                    } else {
                        $this->request->data['SubMenuRodape']['ordem'] = 1;
                    }
                    $menu = $this->SubMenuRodape->save($this->data);
                    $this->Session->setFlash(__('O menu foi cadastrado com sucesso.'), 'sucesso');
                    $this->redirect(array('action'=>'admin_subRodape/'. $menu['SubMenuRodape']['codigo_menu_rodape']));
                } catch (Exception $e) {
                    $this->Session->setFlash(__('Erro ao cadastrar o menu - ' . $e->getMessage() ), 'erro');
                }
            }
            
            $this->set('optionsLinks', $this->carregarLinksRodape());
            $this->set('optionsMenus', $this->carregarMenuRodape());
        }
        
        function admin_editSubRodape($codigo = null){
            
            if(!empty($this->data)){
                
                // Veririca se ele mudou de MenuRodape
                $menuAtual = $this->SubMenuRodape->find('first', array('conditions' => array(
                            'SubMenuRodape.codigo' => $this->data['SubMenuRodape']['codigo'])));
                
                if($menuAtual['SubMenuRodape']['codigo_menu_rodape'] != $this->data['SubMenuRodape']['codigo_menu_rodape']){
                    // Seleciona a última ordem cadastrada no MenuRodape
                    $total = $this->SubMenuRodape->query('SELECT ordem as Total__ordem FROM sub_menu_rodape WHERE codigo_menu_rodape = '. $this->data['SubMenuRodape']['codigo_menu_rodape'] . ' ORDER BY ordem DESC LIMIT 1');
                    if(!empty($total)){
                        $this->request->data['SubMenuRodape']['ordem'] = $total[0]['total']['ordem'] + 1;
                    } else {
                        $this->request->data['SubMenuRodape']['ordem'] = 1;
                    }
                }
                
                try {
                    $menu = $this->SubMenuRodape->save($this->data);
                    $this->Session->setFlash(__('O menu foi alterado com sucesso.'), 'sucesso');
                    $this->redirect(array('action'=>'admin_subRodape/'. $menu['SubMenuRodape']['codigo_menu_rodape']));
                } catch (Exception $e) {
                    $this->Session->setFlash(__('Erro ao alterar o menu.'), 'erro');
                }
            }
            
            $this->SubMenuRodape = $this->SubMenuRodape->find('first', array('conditions' => array('SubMenuRodape.codigo' => $codigo)));
            $this->request->data = $this->SubMenuRodape;
            
            $this->set('optionsLinks', $this->carregarLinksRodape());
            $this->set('optionsMenus', $this->carregarMenuRodape());
        }
        
        function admin_subRodapeUp($codigo = null){
            
            $rodapeUp = $this->SubMenuRodape->find('first', array('conditions' => array('SubMenuRodape.codigo' => $codigo)));
            $rodapeDown = $this->SubMenuRodape->find('first', 
                    array('conditions' => array('SubMenuRodape.ordem >' =>  ($rodapeUp['SubMenuRodape']['ordem']), 
                                                'SubMenuRodape.codigo_menu_rodape' => $rodapeUp['SubMenuRodape']['codigo_menu_rodape']),
                          'order'=> 'SubMenuRodape.ordem ASC'));
            if(!empty($rodapeDown['SubMenuRodape']['codigo'])){
                $dataSource = $this->SubMenuRodape->getDataSource();
                try{
                    $dataSource->begin();
                    
                    $rodapeUp['SubMenuRodape']['ordem'] = $rodapeDown['SubMenuRodape']['ordem'];
                    $rodapeDown['SubMenuRodape']['ordem'] = $rodapeDown['SubMenuRodape']['ordem'] - 1;
                    
                    $this->SubMenuRodape->save($rodapeUp);
                    $this->SubMenuRodape->save($rodapeDown);
                    $dataSource->commit();
                    $this->Session->setFlash(__('O menu foi movido com sucesso.'), 'sucesso');
                } catch (Exception $e){
                   $dataSource->rollback();
                   $this->Session->setFlash(__('Erro ao cadastrar a notícia! -> ' . $e->getMessage()), 'erro'); 
                }
            }            
            
            $this->redirect(array('action'=>'admin_subRodape/'.$rodapeUp['MenuRodape']['codigo']));
        }
        
        function admin_subRodapeDown($codigo = null){

            $rodapeDown = $this->SubMenuRodape->find('first', array('conditions' => array('SubMenuRodape.codigo' => $codigo)));
            $rodapeUp = $this->SubMenuRodape->find('first', 
                    array('conditions' => array('SubMenuRodape.ordem <' =>  ($rodapeDown['SubMenuRodape']['ordem']),
                                                'SubMenuRodape.codigo_menu_rodape' => $rodapeDown['SubMenuRodape']['codigo_menu_rodape']),
                          'order' => 'SubMenuRodape.ordem DESC'));
            
            if(!empty($rodapeUp['SubMenuRodape']['codigo'])){
                $dataSource = $this->SubMenuRodape->getDataSource();
                try{
                    $dataSource->begin();
                    $rodapeDown['SubMenuRodape']['ordem'] = $rodapeUp['SubMenuRodape']['ordem'];
                    $rodapeUp['SubMenuRodape']['ordem'] = $rodapeUp['SubMenuRodape']['ordem'] + 1;
                    $this->SubMenuRodape->save($rodapeDown);
                    $this->SubMenuRodape->save($rodapeUp);
                    $dataSource->commit();
                    $this->Session->setFlash(__('O menu foi movido com sucesso.'), 'sucesso');
                } catch (Exception $e){
                   $dataSource->rollback();
                   $this->Session->setFlash(__('Erro ao cadastrar a notícia! -> ' . $e->getMessage()), 'erro'); 
                }
            }
            
            $this->redirect(array('action'=>'admin_subRodape/'.$rodapeDown['MenuRodape']['codigo']));
        }
        
        private function carregarMenuRodape(){
            $menus = $this->MenuRodape->find('all');
            $optionsMenus = array();
            foreach ($menus as $menu) {
                $optionsMenus[$menu['MenuRodape']['codigo']] = $menu['MenuRodape']['descricao'];
            }
            
            return $optionsMenus;
        }
        
        private function carregarLinksRodape(){
            $links = $this->Pagina->find('all');
            $optionsLinkss = array();
            foreach ($links as $link) {
                $optionsLinks[$link['Pagina']['slug']] = $link['Pagina']['titulo'];
            }
            
            return $optionsLinks;
        }
        
        public function montarRodape(){
            $rodape = $this->MenuRodape->find('all', array('order' => 'ordem DESC'));
            $rodapeAux = array();
            $aux = 0;
            foreach ($rodape as $menu){
                $subMenu = $this->SubMenuRodape->find('all', 
                        array('conditions' => array('SubMenuRodape.codigo_menu_rodape' => $menu['MenuRodape']['codigo']),
                              'order' => 'SubMenuRodape.ordem DESC'));
                $menu['SubMenuRodape'] = $subMenu;
                $rodapeAux[$aux++] = $menu;
            }
            return $rodapeAux;
        }
        
        public function admin_montarRodape(){
            $rodape = $this->MenuRodape->find('all', array('order' => 'ordem DESC'));
            $rodapeAux = array();
            $aux = 0;
            foreach ($rodape as $menu){
                $subMenu = $this->SubMenuRodape->find('all', 
                        array('conditions' => array('SubMenuRodape.codigo_menu_rodape' => $menu['MenuRodape']['codigo']),
                              'order' => 'SubMenuRodape.ordem DESC'));
                $menu['SubMenuRodape'] = $subMenu;
                $rodapeAux[$aux++] = $menu;
            }
            return $rodapeAux;
        }

    }

?>