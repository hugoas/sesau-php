<?php

    define('DIRETORIO_IMAGENS', WWW_ROOT . "arquivos". DS ."fotos" . DS);
    define('DIRETORIO_ANEXOS', WWW_ROOT . "arquivos". DS ."anexos" . DS);
    
    class NoticiasController extends AppController {

        var $name = 'Noticias';
        var $uses = array('Noticia', 'Autor', 'Fonte', 
                          'Fotografo', 'Imagem', 'NoticiaImagem', 'NoticiaAutor',
                          'Vocabulario', 'NoticiaVocabulario', 'ConsultaPublica', 
                          'Legislacao', 'Boletim', 'Edital', 'NoticiaAnexo');

        public $components = array('Util');

        function beforeFilter() {
            $this->Auth->allow('view', 'listar', 'listarTudo', 'impressao', 'outrasNoticias');
            parent::beforeFilter();
        }

        public function admin_index() {
            $this->layout = 'admin';
            $this->verificarPerfil();
            if( !$this->request->is('ajax')){
                $this->request->params['named']['page'] = 1;
            }
            
            if(isset($this->request->query['busca'])){  
                $opcoes['conditions']['and']['Noticia.titulo ilike'] = "%{$this->request->query['busca']}%";
            }
            
            $opcoes['limit'] = 20;
            $opcoes['order'] = array('Noticia.codigo' => 'DESC');
            
            $this->paginate = $opcoes;
            $this->Noticia->recursive = 1;

            $this->set('noticias', $this->paginate());
        }
        
        public function view($noticia = null){
            $noticia = $this->Noticia->find('first',array('conditions' => array('Noticia.codigo'=>$noticia)));
            
            if(empty($noticia['Noticia']['codigo'])){
                $this->Session->setFlash(__('Notícia não encontrada.'), 'erro');
                $this->redirect(array('controller' => '', 'action' => "index"));
            }
            
            $this->set('noticia', $noticia);
            $this->set('area', parent::CIDADAO);
            parent::carregarDadosBarraCidadao();
        }
        
        public function impressao($noticia = null){
            $this->layout = 'impressao';
            $noticia = $this->Noticia->find('first',array('conditions' => array('Noticia.codigo'=>$noticia)));
            $this->set('noticia', $noticia);
        }
        
        private function verificarPerfil(){
            $usuario = $this->Session->read('Auth.User');

            if($usuario['codigo_perfil'] != '1' && $usuario['codigo_perfil'] != '2'){
                $this->Session->setFlash(__('Usuário não tem permissão para acessar esse recurso.'), 'erro');
                $this->redirect(array('controller' => '', 'action' => "index"));
            }
        }
        
        public function admin_add(){
            
            $this->layout = 'admin';
            $this->verificarPerfil();
            if(!empty($this->data)){
                $dataSource = $this->Noticia->getDataSource();

                if(empty($this->data['Noticia']['conteudo'])){
                    $this->Session->setFlash(__('O conteúdo da notícia não pode ser vazio.'), 'erro');
                } if(empty($this->data['Noticia']['codigo_autor'])){
                    $this->Session->setFlash(__('Selecione um ou mais autor(es).'), 'erro');
                } if(empty($this->data['Noticia']['codigo_vocabulario'])){
                    $this->Session->setFlash(__('Selecione um ou mais vocabulário(s).'), 'erro');
                } else {
                    try {
                        $permitidos = array('image/jpg', 'image/jpeg', 'image/gif', 'image/png');

                        $data = date('d-m-Y_H-i-s');
                        if(!empty($this->data['Imagem']['principal']['name'])){

                            if(in_array($this->data['Imagem']['principal']['type'], $permitidos)){
                                if($this->data['Imagem']['principal']['size'] <= AppController::QUATROCENTOS_KB){
                                    $erros['erros'][0] = 'Não houve erro';
                                    $erros['erros'][1] = 'O arquivo no upload é maior do que o limite permitido';
                                    $erros['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especifiado no HTML';
                                    $erros['erros'][3] = 'O upload do arquivo foi feito parcialmente';
                                    $erros['erros'][4] = 'Não foi feito o upload do arquivo';

                                    if ($this->data['Imagem']['principal']['error'] != 0) {
                                        throw new Exception($erros['erros'][$this->data['anexo']['error']]);
                                    }

                                    $this->request->data['Noticia']['nome_imagem'] = "foto_" . $data . "_" . $this->Util->retiraAcentos($this->data['Imagem']['principal']['name']);
                                    $uploadfile = DIRETORIO_IMAGENS . basename("foto_" . $data . "_" . $this->Util->retiraAcentos($this->data['Imagem']['principal']['name']));
                                    move_uploaded_file($this->data['Imagem']['principal']['tmp_name'], $uploadfile);
                                } else {
                                    throw new Exception('O arquivo no upload é maior do que o limite permitido');
                                }
                            } else {
                                throw new Exception('Formato de arquivo inválido');
                            }
                        }
                        $dataSource->begin();
                        $this->NoticiaAutor;
                        $this->NoticiaImagem;
                        $this->NoticiaVocabulario;
                        $this->NoticiaAnexo;

                        $noticia = $this->Noticia->save($this->request->data);

                        foreach ($this->data['Noticia']['codigo_autor'] as $codigo_autor) {
                            $this->NoticiaAutor->create();
                            $this->NoticiaAutor->codigo_noticia = $noticia['Noticia']['codigo'];
                            $this->NoticiaAutor->codigo_autor = $codigo_autor;
                            $this->NoticiaAutor->save($this->NoticiaAutor);
                        }

                        foreach ($this->data['Noticia']['codigo_vocabulario'] as $codigo_vocabulario) {
                            $this->NoticiaVocabulario->create();
                            $this->NoticiaVocabulario->codigo_noticia = $noticia['Noticia']['codigo'];
                            $this->NoticiaVocabulario->codigo_vocabulario = $codigo_vocabulario;
                            $this->NoticiaVocabulario->save($this->NoticiaVocabulario);
                        }

                        if(!empty($this->data['Imagem']['outras'])){
                            foreach ($this->data['Imagem']['outras'] as $imagem) {
                                if(in_array($imagem['type'], $permitidos)){
                                    if($imagem['size'] <= AppController::QUATROCENTOS_KB){
                                        $uploadfile = DIRETORIO_IMAGENS . basename("foto_" . $data . "_" . $noticia['Noticia']['codigo'] . "_" . $this->Util->retiraAcentos($imagem['name']));
                                        move_uploaded_file($imagem['tmp_name'], $uploadfile);
                                        $this->Imagem->create();
                                        $this->Imagem->nome = "foto_" . $data . "_" . $noticia['Noticia']['codigo'] . "_" . $this->Util->retiraAcentos($imagem['name']);
                                        $this->Imagem->descricao = $imagem['descricao'];
                                        $imagem = $this->Imagem->save($this->Imagem);

                                        $this->NoticiaImagem->create();
                                        $this->NoticiaImagem->codigo_noticia = $noticia['Noticia']['codigo'];
                                        $this->NoticiaImagem->codigo_imagem = $imagem['Imagem']['codigo'];
                                        $this->NoticiaImagem->save($this->NoticiaImagem);
                                    } else {
                                        throw new Exception('O arquivo no upload é maior do que o limite permitido');
                                    }
                                } else {
                                    throw new Exception('Formato de arquivo inválido');
                                }
                            }
                        }
                        
                        if(!empty($this->data['Imagem']['outras_noticias'])){
                            foreach ($this->data['Imagem']['outras_noticias'] as $indice => $codigoImagem) {
                                $this->NoticiaImagem->create();
                                $this->NoticiaImagem->codigo_noticia = $noticia['Noticia']['codigo'];
                                $this->NoticiaImagem->codigo_imagem = $codigoImagem;
                                $this->NoticiaImagem->save($this->NoticiaImagem);
                            }
                        }
                        
                        $permitidosAnexo = array(
                            'application/pdf', 
                            'application/msword', 
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel', 
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint');
                        
                        if(!empty($this->data['Anexo'])){
                            foreach ($this->data['Anexo'] as $anexo) {
                                if(in_array($anexo['type'], $permitidosAnexo)){
                                    if($anexo['size'] < AppController::QUATRO_MB){
                                        $uploadfile = DIRETORIO_ANEXOS . basename("anexo_" . $data . "_" . $this->Util->retiraAcentos($anexo['name']));
                                        move_uploaded_file($anexo['tmp_name'], $uploadfile);
                                        $this->NoticiaAnexo->create();
                                        $this->NoticiaAnexo->codigo_noticia = $noticia['Noticia']['codigo'];
                                        $this->NoticiaAnexo->anexo = "anexo_" . $data . "_" . $this->Util->retiraAcentos($anexo['name']);
                                        $this->NoticiaAnexo->save($this->NoticiaAnexo);
                                    } else {
                                        throw new Exception('O arquivo no upload é maior do que o limite permitido');
                                    }
                                } else {
                                   throw new Exception('Formato de arquivo inválido');
                                }
                            }
                        }

                        $dataSource->commit();
                        $this->Session->setFlash(__('Notícia cadastrada com sucesso.'), 'sucesso');
                        $this->redirect(array('action'=>'admin_index'));

                    } catch (Exception $e) {
                        $dataSource->rollback();
                        $this->Session->setFlash(__('Erro ao cadastrar a notícia! -> ' . $e->getMessage()), 'erro');
                    }
                }
            }

            $this->set('autores', $this->carregarAutores());
            $this->set('optionsFontes', $this->carregarFontes());
            $this->set('optionsFotografos', $this->carregarFotografos());
            $this->set('vocabularios', $this->carregarVocabularios());
            $this->set('meses', $this->getMeses());
            $this->set('optionsNoticiasComImagens', $this->carregarNoticiasComImagens());
        }
        
        public function admin_carregarImagensNoticia($codigo = null) {
            $this->layout = "ajax";
            $this->set("imagensNoticias", $this->NoticiaImagem->find("all", array(
                "conditions" => array("NoticiaImagem.codigo_noticia" => $codigo))));
        }
        
        private function carregarAutores(){
            return $this->Autor->find('all', array('conditions' => array('Autor.status'=>true)));
        }
        
        private function carregarFontes(){
            $fontes = $this->Fonte->find('all', array('conditions' => array('Fonte.status'=>true)));
            $optionsFontes = array();
            foreach ($fontes as $fonte) {
                $optionsFontes[$fonte['Fonte']['codigo']] = $fonte['Fonte']['nome'];
            }
            
            return $optionsFontes;
        }
        
        private function carregarFotografos(){
            $fotografos = $this->Fotografo->find('all', array('conditions' => array('Fotografo.status'=>true)));
            $optionsFotografos = array();
            foreach ($fotografos as $fotografo) {
                $optionsFotografos[$fotografo['Fotografo']['codigo']] = $fotografo['Fotografo']['nome'];
            }
            
            return $optionsFotografos;
        }
        
        private function carregarNoticiasComImagens() {
            $noticias = $this->Noticia->find("all", array("conditions" => array(
                " exists (select * from noticia_imagem where noticia_imagem.codigo_noticia = Noticia.codigo)"),
                "order by" => "Noticia.titulo"));
            
            $optionsNoticias = array();
            foreach ($noticias as $noticia) {
                $optionsNoticias[$noticia['Noticia']['codigo']] = $noticia['Noticia']['titulo'];
            }
            return $optionsNoticias;
        }
        
        public function admin_edit($codigo = null){
            $this->layout = 'admin';
            $this->verificarPerfil();

            $this->NoticiaImagem;
            $this->Imagem;
            $this->NoticiaAutor;
            $this->NoticiaVocabulario;
            $this->NoticiaAnexo;

            $noticia = $this->Noticia->find('first', array('conditions' => array('Noticia.codigo' => $codigo)));

            if(!empty($this->data)){

                $dataSource = $this->Noticia->getDataSource();

                if(empty($this->data['Noticia']['conteudo'])){
                    $this->Session->setFlash(__('O conteúdo da notícia não pode ser vazio.'), 'erro');
                } if(empty($this->data['Noticia']['codigo_autor'])){
                    $this->Session->setFlash(__('Selecione um ou mais autor(es).'), 'erro');
                } if(empty($this->data['Noticia']['codigo_vocabulario'])){
                    $this->Session->setFlash(__('Selecione um ou mais vocabulário(s).'), 'erro');
                } else {
                    try {
                        $dataSource->begin();
                        $data = date('d-m-Y_H-i-s');

                        $noticiaAntiga = $this->Noticia->find('first', array('conditions' => array('Noticia.codigo' => $this->data['Noticia']['codigo'])));
                        $permitidos = array('image/jpg', 'image/jpeg', 'image/gif', 'image/png');

                        if(!empty($this->data['Imagem']['principal']['name'])){
                            if(in_array($this->data['Imagem']['principal']['type'], $permitidos)){
                                if($this->data['Imagem']['principal']['size'] <= AppController::QUATROCENTOS_KB){
                                    $this->request->data['Noticia']['nome_imagem'] = "foto_" . $data . "_" . $this->Util->retiraAcentos($this->data['Imagem']['principal']['name']);
                                    $uploadfile = DIRETORIO_IMAGENS . basename("foto_" . $data . "_" . $this->Util->retiraAcentos($this->data['Imagem']['principal']['name']));
                                    move_uploaded_file($this->data['Imagem']['principal']['tmp_name'], $uploadfile);
                                    if(file_exists(DIRETORIO_IMAGENS . $noticiaAntiga['Noticia']['nome_imagem'])){
                                        @unlink(DIRETORIO_IMAGENS . $noticiaAntiga['Noticia']['nome_imagem']);
                                    }
                                } else {
                                    throw new Exception('O arquivo no upload é maior do que o limite permitido');
                                }
                            } else {
                                throw new Exception('Formato de arquivo inválido');
                            }
                        } else if(empty($this->data['Noticia']['nome_imagem'])){
                            $this->request->data['Noticia']['nome_imagem'] =  "";
                            $this->request->data['Noticia']['descricao_imagem'] =  "";
                        }

                        $this->request->data['Noticia']['atualizacao'] = date('Y-m-d H:i:s');
                        $noticia = $this->Noticia->save($this->data);

                        if(!empty($this->data['Imagem']['outras'])){
                            foreach ($this->data['Imagem']['outras'] as $imagem) {
                                if(!isset($imagem['codigo'])){
                                    if(in_array($imagem['type'], $permitidos)){
                                        if($imagem['size'] <= AppController::QUATROCENTOS_KB){
                                            $uploadfile = DIRETORIO_IMAGENS . basename("foto_" . $data . "_" . $codigo . "_" . $this->Util->retiraAcentos($imagem['name']));
                                            move_uploaded_file($imagem['tmp_name'], $uploadfile);
                                            $this->Imagem->create();
                                            $this->Imagem->nome = "foto_" . $data . "_" . $codigo . "_" . $this->Util->retiraAcentos($imagem['name']);
                                            $this->Imagem->descricao = $imagem['descricao'];
                                            $imagem = $this->Imagem->save($this->Imagem);

                                            $this->NoticiaImagem->create();
                                            $this->NoticiaImagem->codigo_noticia = $noticia['Noticia']['codigo'];
                                            $this->NoticiaImagem->codigo_imagem = $imagem['Imagem']['codigo'];
                                            $this->NoticiaImagem->save($this->NoticiaImagem);
                                        } else {
                                            throw new Exception('O arquivo no upload é maior do que o limite permitido');
                                        }
                                    } else {
                                        throw new Exception('Formato de arquivo inválido');
                                    }
                                }
                            }
                        }
                        
                        if(!empty($this->data['Imagem']['outras_noticias'])){
                            foreach ($this->data['Imagem']['outras_noticias'] as $indice => $codigoImagem) {
                                $this->NoticiaImagem->create();
                                $this->NoticiaImagem->codigo_noticia = $noticia['Noticia']['codigo'];
                                $this->NoticiaImagem->codigo_imagem = $codigoImagem;
                                $this->NoticiaImagem->save($this->NoticiaImagem);
                            }
                        }

                        $imagensParaRemover = array();
                        if(!empty($this->data['Imagem']['remover'])){
                            foreach ($this->data['Imagem']['remover'] as $imagem) {
                                $imagensParaRemover[] = $imagem['codigo'];
                            }
                        }

                        if(!empty($imagensParaRemover)){
                            foreach ($imagensParaRemover as $imagem) {
                                $this->NoticiaImagem->query("DELETE FROM portal.noticia_imagem WHERE codigo_noticia = " . $noticia['Noticia']['codigo'] . " AND codigo_imagem = $imagem");
                                $imagemExisteOutraNoticia = $this->NoticiaImagem->findByCodigoImagem($imagem);
                                if(empty($imagemExisteOutraNoticia)) {
                                    $imagemParaRemover = $this->Imagem->find('first', array('conditions' => array('Imagem.codigo' => $imagem)));
                                    $this->Imagem->delete($imagemParaRemover['Imagem']['codigo']);
                                    if(file_exists(DIRETORIO_IMAGENS . $imagemParaRemover['Imagem']['nome'])){
                                        @unlink(DIRETORIO_IMAGENS . $imagemParaRemover['Imagem']['nome']);
                                    }
                                }
                            }
                        }
                        
                        $anexosParaRemover = array();
                        if(!empty($this->data['Anexo']['remover'])){
                            foreach ($this->data['Anexo']['remover'] as $anexo) {
                                $anexosParaRemover[] = $anexo['codigo'];
                            }
                        }

                        if(!empty($anexosParaRemover)){
                            foreach ($anexosParaRemover as $anexo) {
                                $anexoParaRemover = $this->NoticiaAnexo->field("anexo", array('NoticiaAnexo.codigo' => $anexo));
                                $this->NoticiaAnexo->delete($anexo);
                                if(file_exists(DIRETORIO_ANEXOS . $anexoParaRemover)){
                                    @unlink(DIRETORIO_ANEXOS . $anexoParaRemover);
                                }
                            }
                        }
                        
                        $permitidosAnexo = array(
                            'application/pdf', 
                            'application/msword', 
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel', 
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint');
                        
                        if(!empty($this->data['Anexo']['add'])){
                            foreach ($this->data['Anexo']['add'] as $anexo) {
                                if(in_array($anexo['type'], $permitidosAnexo)){
                                    if($anexo['size'] < AppController::QUATRO_MB){
                                        $uploadfile = DIRETORIO_ANEXOS . basename("anexo_" . $data . "_" . $this->Util->retiraAcentos($anexo['name']));
                                        move_uploaded_file($anexo['tmp_name'], $uploadfile);
                                        $this->NoticiaAnexo->create();
                                        $this->NoticiaAnexo->codigo_noticia = $noticia['Noticia']['codigo'];
                                        $this->NoticiaAnexo->anexo = "anexo_" . $data . "_" . $this->Util->retiraAcentos($anexo['name']);
                                        $this->NoticiaAnexo->save($this->NoticiaAnexo);
                                    } else {
                                        throw new Exception('O arquivo no upload é maior do que o limite permitido');
                                    }
                                } else {
                                   throw new Exception('Formato de arquivo inválido');
                                }
                            }
                        }

                        $autoresAntigos = array();
                        foreach ($noticiaAntiga['Autor'] as $autor) {
                            $autoresAntigos[] = $autor['NoticiaAutor']['codigo_autor'];
                        }

                        $autoresNovos = array();
                        foreach ($this->data['Noticia']['codigo_autor'] as $codigo_autor) {
                            $autoresNovos[] = $codigo_autor;
                        }

                        $autoresParaRemover = array_diff($autoresAntigos, $autoresNovos);
                        if(!empty($autoresParaRemover)){
                            foreach ($autoresParaRemover as $autor) {
                                $this->NoticiaAutor->query("DELETE FROM portal.noticia_autor WHERE codigo_noticia = " . $noticia['Noticia']['codigo'] . " AND codigo_autor = $autor");
                            }
                        }

                        $autoresParaAdicionar = array_diff($autoresNovos, $autoresAntigos);

                        if(!empty($autoresParaAdicionar)){
                            foreach ($autoresParaAdicionar as $codigo_autor) {
                                $this->NoticiaAutor->create();
                                $this->NoticiaAutor->codigo_noticia = $this->data['Noticia']['codigo'];
                                $this->NoticiaAutor->codigo_autor = $codigo_autor;
                                $this->NoticiaAutor->save($this->NoticiaAutor);

                            }
                        }

                        $vocabulariosAntigos = array();
                        foreach ($noticiaAntiga['Vocabulario'] as $vocabulario) {
                            $vocabulariosAntigos[] = $vocabulario['NoticiaVocabulario']['codigo_vocabulario'];
                        }

                        $vocabulariosNovos = array();
                        foreach ($this->data['Noticia']['codigo_vocabulario'] as $codigo_vocabulario) {
                            $vocabulariosNovos[] = $codigo_vocabulario;
                        }

                        $vocabulariosParaRemover = array_diff($vocabulariosAntigos, $vocabulariosNovos);
                        if(!empty($vocabulariosParaRemover)){
                            foreach ($vocabulariosParaRemover as $vocabulario) {
                                $this->NoticiaVocabulario->query("DELETE FROM portal.noticia_vocabulario WHERE codigo_noticia = " . $noticia['Noticia']['codigo'] . " AND codigo_vocabulario = $vocabulario");
                            }
                        }

                        $vocabulariosParaAdicionar = array_diff($vocabulariosNovos, $vocabulariosAntigos);

                        if(!empty($vocabulariosParaAdicionar)){
                            foreach ($vocabulariosParaAdicionar as $codigo_vocabulario) {
                                $this->NoticiaVocabulario->create();
                                $this->NoticiaVocabulario->codigo_noticia = $this->data['Noticia']['codigo'];
                                $this->NoticiaVocabulario->codigo_vocabulario = $codigo_vocabulario;
                                $this->NoticiaVocabulario->save($this->NoticiaVocabulario);

                            }
                        }

                        $dataSource->commit();
                        $this->Session->setFlash(__('Notícia cadastrada com sucesso.'), 'sucesso');
                        $this->redirect(array('action'=>'admin_index'));
                    } catch (Exception $e){
                        $dataSource->rollback();
                        $this->Session->setFlash(__('Erro ao cadastrar a notícia! -> ' . $e->getMessage()), 'erro');
                    }
                }
            }

            $this->request->data = $this->Noticia->find('first', array('conditions' => array('Noticia.codigo' => $codigo)));

            $autoresSelecionados = array();
            foreach ($this->data['Autor'] as $autor) {
                $autoresSelecionados[] = $autor['codigo'];
            }

            $this->set('autoresSelecionados', $autoresSelecionados);

            $this->set('autores', $this->carregarAutores());
            $this->set('optionsFontes', $this->carregarFontes());
            $this->set('optionsFotografos', $this->carregarFotografos());
            $this->set('vocabularios', $this->carregarVocabularios());
            $this->set('meses', $this->getMeses());
            $this->set('optionsNoticiasComImagens', $this->carregarNoticiasComImagens());

            $vocabulariosSelecionados = array();
            foreach ($this->data['Vocabulario'] as $vocabulario) {
                $vocabulariosSelecionados[] = $vocabulario['codigo'];
            }
            $this->set('vocabulariosSelecionados', $vocabulariosSelecionados);
        }
        
        public function admin_carregar_vocabulario($codigo = null){
            $this->layout = 'ajax';
            $vocabulario = $this->Vocabulario->find('first', array('conditions' => array('Vocabulario.codigo' => $codigo)));
            $this->set('vocabulario', $vocabulario);
        }
        
        public function admin_carregar_autor($codigo = null){
            $this->layout = 'ajax';
            $autor = $this->Autor->find('first', array('conditions' => array('Autor.codigo' => $codigo)));
            $this->set('autor', $autor);
        }
        
        public function listar($codigoVocabulario = 0){
            
            $this->paginate = array(
                'limit'=>'20',
                'order'=> array('Noticia.codigo'=>'DESC'),
                'group'=>array('Noticia.codigo', 'User.codigo', 'Fotografo.codigo', 'Fonte.codigo'),
                'joins'=> array(
                            array('table'=>'noticia_vocabulario',
                                  'alias'=>'NoticiaVocabulario',
                                  'type'=>'INNER',
                                  'conditions'=> array('Noticia.codigo = NoticiaVocabulario.codigo_noticia AND NoticiaVocabulario.codigo_vocabulario = ' . $codigoVocabulario)
                                )
                            )
                );
            
            $this->Noticia->recursive = 1;
            
            $this->set('noticias', $this->paginate());
            parent::carregarDadosBarraCidadao();
        }
        
        public function listarTudo($codigoSubArea){
            $this->paginate = array(
                    'limit'=>'20',
                    'order'=> array('Noticia.codigo'=>'DESC'),
                    'group'=>array('Noticia.codigo', 'User.codigo', 'Fotografo.codigo', 'Fonte.codigo'),
                    'joins'=> array(
                                array('table'=>'noticia_vocabulario',
                                      'alias'=>'NoticiaVocabulario',
                                      'type'=>'INNER',
                                      'conditions'=> array('Noticia.codigo = NoticiaVocabulario.codigo_noticia')
                                ),
                                array('table'=>'vocabulario',
                                      'alias'=>'Vocabulario',
                                      'type'=>'INNER',
                                      'conditions'=> array('Vocabulario.codigo = NoticiaVocabulario.codigo_vocabulario')
                                ),
                                array('table'=>'sub_area',
                                      'alias'=>'SubArea',
                                      'type'=>'INNER',
                                      'conditions'=> array('SubArea.codigo = Vocabulario.codigo_sub_area AND SubArea.codigo = ' . $codigoSubArea)
                                )
                        )
                    );
            
            $this->Noticia->recursive = 1;
            
            $this->set('noticias', $this->paginate());
            $this->set('area', $codigoSubArea);
            parent::carregarDadosBarraCidadao();
        }
        
        public function outrasNoticias($codigoArea){
            $this->paginate = array(
                'limit'=>'20',
                'order'=> array('Noticia.codigo'=>'DESC'),
                'group'=>array('Noticia.codigo', 'User.codigo', 'Fotografo.codigo', 'Fonte.codigo'),
                'joins'=> array(
                            array('table'=>'noticia_vocabulario',
                                  'alias'=>'NoticiaVocabulario',
                                  'type'=>'INNER',
                                  'conditions'=> array('Noticia.codigo = NoticiaVocabulario.codigo_noticia')
                            ),
                            array('table'=>'vocabulario',
                                  'alias'=>'Vocabulario',
                                  'type'=>'INNER',
                                  'conditions'=> array('Vocabulario.codigo = NoticiaVocabulario.codigo_vocabulario')
                            ),
                            array('table'=>'sub_area',
                                  'alias'=>'SubArea',
                                  'type'=>'INNER',
                                  'conditions'=> array('SubArea.codigo = Vocabulario.codigo_sub_area AND SubArea.codigo_area = ' . $codigoArea)
                            )
                    )
            );
            
            $this->Noticia->recursive = 1;
            
            $this->set('noticias', $this->paginate());
            $this->set('area', $codigoArea);
            parent::carregarDadosBarraCidadao();
        }
        
        public function admin_delete($codigoNoticia){
            $perfil = $this->Session->read('Auth.User.codigo_perfil');
            if($perfil == $this->perfilAdmin() || $perfil == $this->perfilAscom()){
                $this->NoticiaVocabulario;
                $this->NoticiaImagem;
                $this->NoticiaAutor;

                $this->NoticiaVocabulario->deleteAll(array('NoticiaVocabulario.codigo_noticia' => $codigoNoticia));
                $this->NoticiaImagem->deleteAll(array('NoticiaImagem.codigo_noticia' => $codigoNoticia));
                $this->NoticiaAutor->deleteAll(array('NoticiaAutor.codigo_noticia' => $codigoNoticia));
                $this->Noticia->delete($codigoNoticia);
                $this->Session->setFlash(__('Notícia apagada com sucesso.'), 'sucesso');
                $this->redirect(array('action'=>'admin_index'));
            } else {
                $this->Session->setFlash(__('Você não tem permissão para apagar essa notícia.', 'erro'));
                $this->redirect(array('action'=>'admin_index'));
            }
        }
        
    }

?>