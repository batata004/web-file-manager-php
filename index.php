<?php

set_time_limit(0);
ini_set("memory_limit","-1");
ini_set("pcre.backtrack_limit",1000000000);


$diretorio_root = "/ebs1/apache/www/html";
//$diretorio_root = "Z:/htdocs";

//O valor da variável abaixo deve começar com "https" caso contrário o preview das imagens não funcionará corretamente se a URL utilizada para acessar esse sistema possua "https".
$url_diretorio_root = "https://acessodireto.quemfazsite.com.br";
//$url_diretorio_root = "http://localhost";





//$url_deste_script = (((isset($_SERVER['HTTPS']))   and   ($_SERVER['HTTPS'] == 'on'))?'https://':'http://') . $_SERVER['HTTP_HOST'] . preg_replace("/[^\/]+$/","",strtok($_SERVER['REQUEST_URI'],'?'));
$url_deste_script = (((isset($_SERVER['HTTPS']))   and   ($_SERVER['HTTPS'] == 'on'))?'https://':'http://') . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'],'?');

$diretorio_arquivo = preg_replace('/\/{2,}/','/',($_GET["diretorio_arquivo"] ?? $diretorio_root));

if (is_dir($diretorio_arquivo)) {
	
	$diretorio_arquivo = rtrim($diretorio_arquivo,'/') . '/';
	
	$diretorio = $diretorio_arquivo; 
	
	$arquivo = false;
	
}
else if (is_file($diretorio_arquivo)) {

	$diretorio = false;
	$arquivo = $diretorio_arquivo;
	$extensao_arquivo = pathinfo($arquivo,PATHINFO_EXTENSION);
	
}
else{
	
	?>
	<meta charset="utf-8">
	<script type="text/javascript">
	alert("O diretório/arquivo \"<?php echo $diretorio_arquivo; ?>\" não foi encontrado no servidor.");
	window.location.href = "<?php echo $url_deste_script; ?>?diretorio_arquivo=<?php echo $diretorio_root; ?>";
	</script>
	<?php
	exit();
	
}





clearstatcache();




/**
 * Class to work with zip files (using ZipArchive)
 */
class FM_Zipper
{
    private $zip;

    public function __construct()
    {
        $this->zip = new ZipArchive();
    }

    public function zip($filename, $files)
    {
        $res = $this->zip->open($filename, ZipArchive::CREATE);
        if ($res !== true) {
            return false;
        }
        if (is_array($files)) {
            foreach ($files as $f) {
                if (!$this->addFileOrDir($f)) {
                    $this->zip->close();
                    return false;
                }
            }
            $this->zip->close();
            return true;
        } else {
            if ($this->addFileOrDir($files)) {
                $this->zip->close();
                return true;
            }
            return false;
        }
    }

    public function unzip($filename, $path)
    {
        $res = $this->zip->open($filename);
        if ($res !== true) {
            return false;
        }
		//O comando abaixo faz com que todos os arquivos extraídos pelo comando "extractTo" possuam o privilégio "777".
		umask(0);
        if ($this->zip->extractTo($path)) {
            $this->zip->close();
            return true;
        }
        return false;
    }

    /**
     * Add file/folder to archive
     * @param string $filename
     * @return bool
     */
    private function addFileOrDir($filename)
    {
        if (is_file($filename)) {
			
			if ($this->zip->addFile($filename)) {
			
				//NV43.
				//$this->zip->setCompressionName($path . '/' . $file,ZipArchive::CM_STORE);	
				
				return true;

			}
			else{
				
				return false;
				
			}
						
        } elseif (is_dir($filename)) {
            return $this->addDir($filename);
        }
        return false;
    }

    /**
     * Add folder recursively
     * @param string $path
     * @return bool
     */
    private function addDir($path)
    {
        if (!$this->zip->addEmptyDir($path)) {
            return false;
        }
        $objects = scandir($path);
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . '/' . $file)) {
                        if (!$this->addDir($path . '/' . $file)) {
                            return false;
                        }
                    } elseif (is_file($path . '/' . $file)) {
						
                        if (!$this->zip->addFile($path . '/' . $file)) {
                            return false;
                        }
						
						//Caso perceba que os arquivos zip estão demorando muito para serem gerados, descomente a linha abaixo em todos os locais que aparece nesse código pois dessa forma o arquivo não será comprimido, o conteúdo será apenas juntado no arquivo zip e isso acelera a geração do arquivo. NV43.
						//$this->zip->setCompressionName($path . '/' . $file,ZipArchive::CM_STORE);
                    }
                }
            }
            return true;
        }
        return false;
    }
}





if (isset($_FILES["conteudo_arquivo"])) {

	$_POST["fullPath"] = ltrim($_POST["fullPath"],"/");

	$temp8844 = explode("/",dirname($_POST["fullPath"]));
	
	$temp1133 = $_POST["diretorio_arquivo"]	;
	
	for ($i=0;$i<count($temp8844);$i++) {
		
		$temp1133 .= '/' . $temp8844[$i];
		
		if (!is_dir($temp1133)) {
			
			$resultado = @mkdir($temp1133);
			
			if ($resultado !== false) {
				
				//Ver "YF34".
				@chmod($temp1133,0777);
				
			}
			
			if ($resultado === false) {
			
				echo json_encode(array('erro' => '<div>Erro HJ54.</div><div>&nbsp;</div><div style="background:rgba(0,0,0,0.2); padding:5px; font-family:Courier;"><span style="font-weight:bold; border-bottom:1px dotted #000;">Informações adicionais:</span> ' . htmlentities(print_r(error_get_last(),true),ENT_QUOTES,"UTF-8") . '</div>;'));
				
				exit();
				
			}
			
		}
		
	}
	
	$resultado = @move_uploaded_file($_FILES["conteudo_arquivo"]["tmp_name"],$_POST["diretorio_arquivo"] . $_POST["fullPath"]);
	
	if ($resultado !== false) {
	
		//A função "chmod" não funcionará quando o "Owner" do arquivo não for o usuário "apache" (mesmo realizando aquela configuração no "visudo" do manual de formatação do CentOS), ou seja, se algum arquivo tiver sido enviado através do FTP utilizando-se o usuário "root", então o comando abaixo retornará erro pois ele será executado pelo usuário "apache". Esse "bug" aparentemente só ocorre com o comando "chmod". YF34.
		@chmod($_POST["diretorio_arquivo"] . $_POST["fullPath"],0777);
		
	}

	if ($resultado === false) {
		
		echo json_encode(array('erro' => '<div>Erro ND54.</div><div>&nbsp;</div><div style="background:rgba(0,0,0,0.2); padding:5px; font-family:Courier;"><span style="font-weight:bold; border-bottom:1px dotted #000;">Informações adicionais:</span> ' . htmlentities(print_r(error_get_last(),true),ENT_QUOTES,"UTF-8") . '</div>;'));
		
		exit();
		
	}
	
	echo json_encode(array('sucesso' => 'O arquivo <span style="font-weight:bold; border-bottom:1px dotted #000;">' . $_POST["diretorio_arquivo"] . $_POST["fullPath"] . '</span> foi salvo com sucesso;'));

	exit();
	
}

if (isset($_GET["acao"])) {



	if ($_GET["acao"] === "baixar_arquivo_zipado_e_remover") {
	
		header('Content-type: application/zip');
		header('Content-Disposition: inline; filename="' . basename($_GET["arquivo_zipado"]) . '"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		ob_clean();
		flush();

		if (readfile($_GET["arquivo_zipado"])) {
			
			if (strpos($_GET["arquivo_zipado"],$diretorio_root) !== 0) {
				
				exit("Erro NR51.");
				
			}	
			
			unlink($_GET["arquivo_zipado"]);
			
		}
	
	}
	
	
	
	exit();
	
}

if (isset($_POST["acao"])) {



	if ($_POST["acao"] === "renomear") {
	
		if (strpos($_POST["nome_atual_diretorio_arquivo"],$diretorio_root) !== 0) {
			
			echo json_encode(array('erro' => 'Erro PE34.'));
			exit();
			
		}
	
		$resultado = @rename($_POST["nome_atual_diretorio_arquivo"],$_POST["novo_nome_diretorio_arquivo"]);

		if ($resultado !== false) {
			
			echo json_encode(array('sucesso' => 'O arquivo/diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["nome_atual_diretorio_arquivo"] . '</span> foi renomeado para <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["novo_nome_diretorio_arquivo"] . '</span> com sucesso!'));
		
		}
		else{
			
			echo json_encode(array('erro' => '<div>Erro ao renomear o arquivo/diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["nome_atual_diretorio_arquivo"] . '</span> para <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["novo_nome_diretorio_arquivo"] . '</span>.</div><div>&nbsp;</div><div style="background:rgba(0,0,0,0.2); padding:5px; font-family:Courier;"><span style="font-weight:bold; border-bottom:1px dotted #000;">Informações adicionais:</span> ' . htmlentities(print_r(error_get_last(),true),ENT_QUOTES,"UTF-8") . '</div>'));
			
		}
		
	}
	else if ($_POST["acao"] === "salvar") {
	
		if (strpos($_POST["diretorio_arquivo"],$diretorio_root) !== 0) {
			
			echo json_encode(array('erro' => 'Erro NM51.'));
			exit();
			
		}
	
		//$encoding_string = detectar_encoding_string(file_get_contents($_POST["diretorio_arquivo"]));
		$encoding_string = $_POST["encoding"];
		
		if ($encoding_string === "Windows-1252") {	
	
			$resultado = @file_put_contents($_POST["diretorio_arquivo"],mb_convert_encoding($_POST["conteudo"],"Windows-1252","UTF-8"),LOCK_EX);

		}
		else if ($encoding_string === "UTF-8") {	

			$resultado = @file_put_contents($_POST["diretorio_arquivo"],$_POST["conteudo"],LOCK_EX);

		}
		else{
			
			echo json_encode(array('erro' => 'Erro ao salvar o arquivo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span> contendo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . strlen($_POST["conteudo"]) . '</span> caracteres pois a codificação <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $encoding_string . '</span> desse arquivo não possui condicional no script.'));
			return false;
			
		}

		if ($resultado !== false) {
			
			echo json_encode(array('sucesso' => 'O arquivo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span>, com encoding <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $encoding_string . '</span> e contendo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . strlen($_POST["conteudo"]) . '</span> caracteres foi salvo com sucesso!'));
		
		}
		else{
			
			echo json_encode(array('erro' => '<div>Erro ao salvar o arquivo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span>, com encoding <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $encoding_string . '</span> e contendo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . strlen($_POST["conteudo"]) . '</span> caracteres.</div><div>&nbsp;</div><div style="background:rgba(0,0,0,0.2); padding:5px; font-family:Courier;"><span style="font-weight:bold; border-bottom:1px dotted #000;">Informações adicionais:</span> ' . htmlentities(print_r(error_get_last(),true),ENT_QUOTES,"UTF-8") . '</div>'));
			
		}
		
	}
	
	
	
	
	else if (   ($_POST["acao"] === "copiar")   or   ($_POST["acao"] === "mover")   ) {

		if (strpos($_POST["destino_copiar_mover_arquivos_diretorios"],$diretorio_root) !== 0) {
			
			echo json_encode(array('erro' => 'Erro WI45.'));
			exit();
			
		}	

		if (is_dir($_POST["destino_copiar_mover_arquivos_diretorios"]) === false) {
			
			echo json_encode(array('erro' => 'Erro ao ' . $_POST["acao"] . ' os arquivos/diretórios pois o diretório de destino <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . '/</span> não existe.'));
			exit();
			
		}
	
		$diretorios_arquivos = json_decode($_POST["diretorios_arquivos"],true);
	
		foreach ($diretorios_arquivos as $chave1 => $valor1) {
			
			if (   (strpos($diretorios_arquivos[$chave1],$diretorio_root) !== 0)   or   (strpos($diretorios_arquivos[$chave1],$_POST["diretorio_arquivo"]) !== 0)   ) {
				
				echo json_encode(array('erro' => 'Erro PE45.'));
				exit();
				
			}	
		
		}
	
		foreach ($diretorios_arquivos as $chave1 => $valor1) {
			
			if (   (is_dir($diretorios_arquivos[$chave1]))   and   (strpos(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/",rtrim($diretorios_arquivos[$chave1],"/") . "/") !== false)   ) {
				
				echo json_encode(array('erro' => 'Erro ao ' . $_POST["acao"] . ' os arquivos/diretórios pois o diretório de destino <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . '/</span> contém o diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . rtrim($diretorios_arquivos[$chave1],"/") . '/</span> a ser copiado/movido.'));
				exit();
				
			}	
		
		}

		foreach ($diretorios_arquivos as $chave1 => $valor1) {
		
			if (substr_count($diretorios_arquivos[$chave1],"/") < 4) {
				
				echo json_encode(array('erro' => 'Erro ao ' . $_POST["acao"] . ' o arquivo/diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span> pois o caminho não possui o número mínimo de "/".'));
				
				exit();
				
			}
		
		}

		function copiar_diretorio_recursivamente($diretorio,$diretorio_acumulado) {
		global $qntd_arquivos_diretorios_copiados;
		
			if (is_dir(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $diretorio_acumulado) === false) {
				
				$resultado = mkdir(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $diretorio_acumulado);
				if ($resultado === false) {
	
					return false;
					
				}
				
				//Ver "YF34".
				@chmod(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $diretorio_acumulado,0777);
			
			}

			if ($handle = opendir($diretorio)) {
			
				while (false !== ($entry = readdir($handle))) {

					if (   ($entry !== ".")   and   ($entry !== "..")   ) {
						
						if (is_dir($diretorio . '/' . $entry)) {
							
							if (!copiar_diretorio_recursivamente($diretorio . '/' . $entry,$diretorio_acumulado . '/' . $entry . '/')) {

								return false;
							
							}
						
						}
						else{
						
							$resultado = @copy($diretorio . '/' . $entry,rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $diretorio_acumulado . $entry);
							
							if ($resultado === false) {

								return false;
								
							}
							
							//Ver "YF34".							
							@chmod(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $diretorio_acumulado . $entry,0777);
							
							++$qntd_arquivos_diretorios_copiados;
						
						}
						
					}
					
				}

			}
			
			return true;
			
		}

		$lista_acoes = array();
	
		foreach ($diretorios_arquivos as $chave1 => $valor1) {
		
			$temp3345 = explode("/",rtrim($diretorios_arquivos[$chave1],"/"));
			$temp3345 = $temp3345[count($temp3345) - 1];
		
			if (   (is_dir(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $temp3345))   or   (is_file(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $temp3345))   ) {
				
				$lista_acoes[] = '<span style="display:none;">ERRO_ERRO_ERRO</span>Erro ao ' . $_POST["acao"] . ' o arquivo/diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span> pois ele já existe em <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $temp3345 . '</span>;';
			
			}
			else{

				if ($_POST["acao"] === "copiar") {	
			
					if (is_file($diretorios_arquivos[$chave1])) {
					
						$resultado = @copy($diretorios_arquivos[$chave1],rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $temp3345);
						
						if ($resultado !== false) {
						
							//Ver "YF34".
							@chmod(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $temp3345,0777);
							
						}
						
						if ($resultado !== false) {
						
							$lista_acoes[] = 'O arquivo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $temp3345 . '</span> foi copiado com sucesso;';
							
						}
						else{
							
							$lista_acoes[] = '<div><span style="display:none;">ERRO_ERRO_ERRO</span>Erro ao executar o comando para copiar o arquivo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span>.</div><div>&nbsp;</div><div style="background:rgba(0,0,0,0.2); padding:5px; font-family:Courier;"><span style="font-weight:bold; border-bottom:1px dotted #000;">Informações adicionais:</span> ' . htmlentities(print_r(error_get_last(),true),ENT_QUOTES,"UTF-8") . '</div>;';
							
						}

					}
					else if (is_dir($diretorios_arquivos[$chave1])) {
						
						$qntd_arquivos_diretorios_copiados = 0;
						$resultado = copiar_diretorio_recursivamente($diretorios_arquivos[$chave1],$temp3345 . '/');
						
						if ($resultado !== false) {
						
							$lista_acoes[] = 'O diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $temp3345 . '</span>, que contém <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $qntd_arquivos_diretorios_copiados . '</span> arquivos/diretórios,  foi copiado com sucesso;';
							
						}
						else{
							
							$lista_acoes[] = '<span style="display:none;">ERRO_ERRO_ERRO</span>Erro ao executar o comando para copiar o diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span>;';
							
						}						
					
					}
					
				}
				else if ($_POST["acao"] === "mover") {	
		
					$resultado = @rename($diretorios_arquivos[$chave1],rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $temp3345);
					
					if ($resultado !== false) {
						
						//Não descomentar a linha abaixo pois quando se move determinado arquivo, principalmente se for algum arquivo de sistema, certamente se deseja manter as mesmas permissões já existentes.
						//Ver "YF34".
						//@chmod(rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . "/" . $temp3345,0777);
						
					}
					
					if ($resultado !== false) {
					
						$lista_acoes[] = 'O arquivo/diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $temp3345 . '</span> foi movido com sucesso;';
						
					}
					else{
						
						$lista_acoes[] = '<span style="display:none;">ERRO_ERRO_ERRO</span>Erro ao executar o comando para mover o arquivo/diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span>.</div><div>&nbsp;</div><div style="background:rgba(0,0,0,0.2); padding:5px; font-family:Courier;"><span style="font-weight:bold; border-bottom:1px dotted #000;">Informações adicionais:</span> ' . htmlentities(print_r(error_get_last(),true),ENT_QUOTES,"UTF-8") . '</div>;';
						
					}

				}
				
			}
			
		}

		echo json_encode(array('sucesso' => 'Confira abaixo o resultado da cópia/movimentação do(s) arquivo(s)/diretório(s) ' . (($temp1177 = substr_count(implode('',$lista_acoes),'ERRO_ERRO_ERRO')) === 0?'':' <span style="font-weight:bold; border-bottom:1px dotted #DB2828; color:#DB2828;">com ' . $temp1177 . ' erro(s)</span>') . ' para <b>' . rtrim($_POST["destino_copiar_mover_arquivos_diretorios"],"/") . '/</b>:<ol align="left"><li>' . implode('</li><li>',$lista_acoes) . '</li></ol>'));
		
	}
	else if ($_POST["acao"] === "remover") {
	
		$diretorios_arquivos = json_decode($_POST["diretorios_arquivos"],true);
	
		foreach ($diretorios_arquivos as $chave1 => $valor1) {
			
			if (   (strpos($diretorios_arquivos[$chave1],$diretorio_root) !== 0)   or   (strpos($diretorios_arquivos[$chave1],$_POST["diretorio_arquivo"]) !== 0)   ) {
				
				echo json_encode(array('erro' => 'Erro WQ78.'));
				exit();
				
			}	
		
		}
		
		foreach ($diretorios_arquivos as $chave1 => $valor1) {
		
			if (substr_count($diretorios_arquivos[$chave1],"/") < 4) {
				
				echo json_encode(array('erro' => 'Erro ao remover o arquivo/diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span> pois o caminho não possui o número mínimo de "/".'));
				
				exit();
				
			}
		
		}

		function remover_diretorio_recursivamente($diretorio) {
		global $qntd_arquivos_diretorios_removidos;
		
			if (!file_exists($diretorio)) {
				
				return true;
				
			}

			if (!is_dir($diretorio)) {
				
				++$qntd_arquivos_diretorios_removidos;
				return @unlink($diretorio);
			
			}

			if ($handle = opendir($diretorio)) {
			
				while (false !== ($entry = readdir($handle))) {

					if (   ($entry !== ".")   and   ($entry !== "..")   ) {
						
						if (!remover_diretorio_recursivamente($diretorio . '/' . $entry)) {
						
							return false;
						
						}
						
					}
					
				}

				++$qntd_arquivos_diretorios_removidos;
				return @rmdir($diretorio);
				
			}
			
		}
	
		$lista_acoes = array();
	
		foreach ($diretorios_arquivos as $chave1 => $valor1) {
		
			if (is_dir($diretorios_arquivos[$chave1])) {
			
				$qntd_arquivos_diretorios_removidos = 0;
				$resultado = remover_diretorio_recursivamente($diretorios_arquivos[$chave1]);
				
				if ($resultado) {
				
					$lista_acoes[] = 'O diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span>, que continha <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $qntd_arquivos_diretorios_removidos . '</span> arquivos/diretórios, foi removido com sucesso;';
					
				}
				else{
					
					$lista_acoes[] = '<span style="display:none;">ERRO_ERRO_ERRO</span>Erro ao executar o comando para remover o diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span>;';
				
				}
			
			}
			else if (is_file($diretorios_arquivos[$chave1])) {
			
				$resultado = unlink($diretorios_arquivos[$chave1]);
				
				if ($resultado !== false) {
				
					$lista_acoes[] = 'O arquivo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span> foi removido com sucesso;';
					
				}
				else{
					
					$lista_acoes[] = '<span style="display:none;">ERRO_ERRO_ERRO</span>Erro ao executar o comando para remover o arquivo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span>;';
					
				}

			
			}
			else{
				
				$lista_acoes[] = '<span style="display:none;">ERRO_ERRO_ERRO</span>Erro ao remover o arquivo/diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $diretorios_arquivos[$chave1] . '</span> pois ele não existe;';
				
			}
			
		}

		// do diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span>
		echo json_encode(array('sucesso' => 'Confira abaixo o resultado da remoção do(s) arquivo(s)/diretório(s) ' . (($temp1177 = substr_count(implode('',$lista_acoes),'ERRO_ERRO_ERRO')) === 0?'':' <span style="font-weight:bold; border-bottom:1px dotted #DB2828; color:#DB2828;">com ' . $temp1177 . ' erro(s)</span>') . ':<ol align="left"><li>' . implode('</li><li>',$lista_acoes) . '</li></ol>'));
		
	}
	else if ($_POST["acao"] === "criar") {
	
		if (strpos($_POST["diretorio_arquivo"],$diretorio_root) !== 0) {
			
			echo json_encode(array('erro' => 'Erro WQ78.'));
			exit();
			
		}	
	
		if ($_POST["tipo_arquivo_diretorio"] === "diretório") {
		
			if (is_dir($_POST["diretorio_arquivo"])) {
				
				echo json_encode(array('erro' => 'Erro ao criar o diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span> pois ele já existe.'));
				exit();
				
			}
			
			$resultado = @mkdir($_POST["diretorio_arquivo"]);
			
			if ($resultado !== false) {
			
				//Ver "YF34".
				@chmod($_POST["diretorio_arquivo"],0777);
				
			}
		
		}
		else if ($_POST["tipo_arquivo_diretorio"] === "arquivo") {
			
			if (is_file($_POST["diretorio_arquivo"])) {
				
				echo json_encode(array('erro' => 'Erro ao criar o arquivo <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span> pois ele já existe.'));
				exit();
				
			}
		
			$resultado = @file_put_contents($_POST["diretorio_arquivo"],"",LOCK_EX);
	
			if ($resultado !== false) {
				
				//Ver "YF34".
				@chmod($_POST["diretorio_arquivo"],0777);
				
			}
	
		}		

		if ($resultado !== false) {		
		
			echo json_encode(array('sucesso' => 'O ' . $_POST["tipo_arquivo_diretorio"] . ' <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span> foi criado com sucesso!'));
			
		}
		else{
			
			echo json_encode(array('erro' => '<div>Erro ao executar o comando para criar o ' . $_POST["tipo_arquivo_diretorio"] . ' <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span>.</div><div>&nbsp;</div><div style="background:rgba(0,0,0,0.2); padding:5px; font-family:Courier;"><span style="font-weight:bold; border-bottom:1px dotted #000;">Informações adicionais:</span> ' . htmlentities(print_r(error_get_last(),true),ENT_QUOTES,"UTF-8") . '</div>'));
			
		}	
	
	}
	else if ($_POST["acao"] === "zip") {
	
		$nome_arquivo_zipado = trim(substr(preg_replace("/[^0-9a-z_\-.]/i","_",str_replace("/","-",$_POST["diretorio_arquivo"])),-100),'-') . '-' . rand(10000,99999) . ".zip";
	
		$arquivos_diretorios_a_serem_zipados = json_decode($_POST["arquivos_diretorios_a_serem_zipados"],true);



		$temp3624 = array();
		
		foreach ($arquivos_diretorios_a_serem_zipados as $chave1 => $valor1) {
			
			$temp3624[] = str_replace(str_replace("\\","/",getcwd()) . "/","",$arquivos_diretorios_a_serem_zipados[$chave1]);
			
		}



		chdir($_POST["diretorio_arquivo"]);

		foreach ($temp3624 as $chave3624 => $valor3624) {
			
			$temp3624[$chave3624] = str_replace($_POST["diretorio_arquivo"],"",$temp3624[$chave3624]);
			
		}

		$zipper = new FM_Zipper();
		$resultado = $zipper->zip($_POST["diretorio_arquivo"] . $nome_arquivo_zipado,$temp3624);

	
		if ($resultado !== false) {
			
			echo json_encode(array('sucesso' => 'No arquivo <a target="_blank" href="' . $url_diretorio_root . str_replace($diretorio_root,"",$_POST["diretorio_arquivo"]) . $nome_arquivo_zipado . '" style="text-decoration:underline; font-weight:bold;">' . $_POST["diretorio_arquivo"] . $nome_arquivo_zipado . '</a> foram zipados os arquivos/diretórios a seguir: <ol align="left"><li>' . implode('</li><li>',$arquivos_diretorios_a_serem_zipados) . '</li></ol>','arquivo_zipado' => $_POST["diretorio_arquivo"] . $nome_arquivo_zipado));
			
		}
		else{
			
			echo json_encode(array('erro' => 'Erro ao executar o comando para zipar os arquivos/diretórios abaixo no diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span>. <ol align="left"><li>' . implode('</li><li>',$arquivos_diretorios_a_serem_zipados) . '</li></ol>'));
			
		}
		
	}
	else if ($_POST["acao"] === "unzip") {

		$zipper = new FM_Zipper();
		$resultado = $zipper->unzip($_POST["arquivo_a_ser_unzipado"],$_POST["diretorio_arquivo"]);

	
		if ($resultado !== false) {
			
			echo json_encode(array('sucesso' => 'O conteúdo do arquivo <a target="_blank" href="' . $url_diretorio_root . str_replace($diretorio_root,"",$_POST["arquivo_a_ser_unzipado"]) . '" style="text-decoration:underline; font-weight:bold;">' . $_POST["arquivo_a_ser_unzipado"] . '</a> foi extraído com sucesso.'));
			
		}
		else{
			
			echo json_encode(array('erro' => 'Erro ao executar o comando para unzipar o arquivo <a target="_blank" href="' . $url_diretorio_root . str_replace($diretorio_root,"",$_POST["arquivo_a_ser_unzipado"]) . '" style="text-decoration:underline; font-weight:bold;">' . $_POST["arquivo_a_ser_unzipado"] . '</a> no diretório <span style="font-weight:bold; border-bottom:1px dotted #000; font-size:13.5px;">' . $_POST["diretorio_arquivo"] . '</span>.'));
			
		}
		
	}


	exit();
	
}
















function detectar_encoding_string($string) {
	
	//Não utilize algo como -->>if (preg_match("/[" . utf8_decode("áãàéèíóõúçÁÃÀÉÈÍÓÕÚÇ") . "]/",$string)) {<<-- pois executar expressões regulares num script rodando em PHP com base numa string em outro encoding acaba gerando erros na detecção. Também não utilize "stripos" pois não funciona corretamente, utilize "strpos".
	
	$temp = array('á','ã','à','é','è','í','ó','õ','ú','ç','Á','Ã','À','É','È','Í','Ó','Õ','Ú','Ç');

	$encontrou_caracter = false;
	
	foreach ($temp as $chave1 => $valor1) {
		
		if (strpos($string,$temp[$chave1]) !== false) {
			
			$encontrou_caracter = true;
			break;
			
		}
		
	}
	
	if ($encontrou_caracter) {

		return "UTF-8";

	}
	else{

		return "Windows-1252";

	}	
	
}

function carregar_arquivo_para_edicao($conteudo_arquivo) {
	
	$encoding_string = detectar_encoding_string($conteudo_arquivo);
	
	if ($encoding_string === "Windows-1252") {

		return str_replace(array("\r","\n",'</script>'),array('','\n','<\/script>'),addslashes(mb_convert_encoding($conteudo_arquivo,"UTF-8","Windows-1252")));
		
	}
	else if ($encoding_string === "UTF-8") {
		
		return str_replace(array("\r","\n",'</script>'),array('','\n','<\/script>'),addslashes($conteudo_arquivo));
		
	}
	else{
		
		exit("Erro KF53.");
		
	}

}














if ($arquivo !== false) {
	
	$conteudo_arquivo = file_get_contents($arquivo);
	
}




?>
<!doctype html>
<html>
<head>

	<meta charset="utf-8">
	
	<title>Administrar arquivos</title>
	
	<link rel="shortcut icon" href="favicon.ico" type="image/ico" />
  
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.7/semantic.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.7/semantic.min.js"></script>

	<?php
	
	if ($arquivo !== false) {
		
		?>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.21.2/min/vs/loader.min.js"></script>
		<?php
	
	}
	
	?>
	
	<style type="text/css">
	ol{
		list-style:none;
		padding-left:10px;
		counter-reset:item;
	}
	ol li:before {
		content:counter(item) ". ";
		counter-increment:item;
		font-weight:bold;
		font-size:14px;
		padding-right:5px;
	}
	ol li{
		margin-bottom:8px;
	}
	.aplicar_efeito_hover_linha_tabela:not(.linha_possui_checkbox_checked):hover{
		background:rgba(0,0,0,0.04);
	}
	.linha_possui_checkbox_checked{
		background:rgba(0,0,0,0.15);
	}
	.encontrou_caracteres_pressionados{
		background:rgba(255,0,0,0.15);
	}
	.input_nome_arquivo_diretorio:focus{
		background:#FFFFFF !important;
	}
	tr{
		user-select:none;		
	}
	</style>
	
	<script>
	var diretorio_root = "<?php echo $diretorio_root; ?>";
	var url_diretorio_root = "<?php echo $url_diretorio_root; ?>";	
	var diretorio_arquivo = "<?php echo $diretorio_arquivo; ?>";
	var url_deste_script = "<?php echo $url_deste_script; ?>";
	
	$(document).ready(function() {
		
		
		<?php
		
		if ($arquivo !== false) {
			
			?>
			$(".dropdown_encoding").dropdown({
			
				on:"hover",
				action:"activate",
				onChange:function(value) {
					
					editor.focus();
					
				}
			
			});
			<?php
			
		}
		
		?>
		
		
		
		<?php
	
		if ($diretorio !== false) {
			
			?>
			$(".dropdown_criar_arquivo_diretorio").dropdown({
			
				on:"hover",
				action:"hide",
				onChange:function(value) {
					
					criar_arquivo_diretorio(value);
					
				}
			
			});

			$(".dropdown_copiar_mover_arquivos_diretorios").dropdown({
			
				on:"hover",
				action:"hide",
				onChange:function(value) {
					
					copiar_mover_arquivos_diretorios(value);
					
				}
			
			});	
	
			$("tr").on(

				"click",
				function(event) {
				
					if (   (event.target !== this)   &&   ($(event.target).is("td") === false)   ) {
					
						return false;
					
					}
				
					//$(this).find(".checkbox_selecionar_arquivo_diretorio").checkbox("toggle");
				
					var simular_evento = $.Event("click");
					simular_evento.shiftKey = event.shiftKey;
				
					$(this).find(".checkbox_selecionar_arquivo_diretorio").trigger(simular_evento);
				
				}

			);			
			
			$(".input_nome_arquivo_diretorio").on(

				"keyup",
				function (event) {

					if (event.keyCode === 13){
					
						$(this).trigger("blur");
					
					}	
				
				}

			);
			
			$("tr").find("i.eye").on(
			
				"mouseenter mouseleave",
				function(event) {
				
					if (event.type === "mouseenter") {
				
						var temp6677 = $(this).parents("tr").find("input[name='selecionar_arquivo_diretorio']").val();
						
						if (temp6677.match(new RegExp("(gif|ico|jpg|png|svg|webp)$","i"))) {
							
							$(this).attr({"data-position-backup":$(this).attr("data-position"),"data-position-backup":$(this).attr("data-position")});
							
							$(this).attr({"data-position":"right center","data-html":'<img id="preview_imagem" src="' + url_diretorio_root + temp6677.replace(diretorio_root,"") + '" style="height:180px; width:180px; border-radius:5px; background:url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiBzdHlsZT0ibWFyZ2luOiBhdXRvOyBiYWNrZ3JvdW5kOiBub25lOyBkaXNwbGF5OiBibG9jazsgc2hhcGUtcmVuZGVyaW5nOiBhdXRvOyIgd2lkdGg9Ijk3cHgiIGhlaWdodD0iOTdweCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIj4KPGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iMzIiIHN0cm9rZS13aWR0aD0iOCIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2UtZGFzaGFycmF5PSI1MC4yNjU0ODI0NTc0MzY2OSA1MC4yNjU0ODI0NTc0MzY2OSIgZmlsbD0ibm9uZSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIj4KICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InJvdGF0ZSIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiIGR1cj0iMi42MzE1Nzg5NDczNjg0MjEycyIga2V5VGltZXM9IjA7MSIgdmFsdWVzPSIwIDUwIDUwOzM2MCA1MCA1MCI+PC9hbmltYXRlVHJhbnNmb3JtPgo8L2NpcmNsZT4KPCEtLSBbbGRpb10gZ2VuZXJhdGVkIGJ5IGh0dHBzOi8vbG9hZGluZy5pby8gLS0+PC9zdmc+) center center no-repeat #000;" onload="$(this).css({width:\'auto\',background:\'#000\'});" />'});
					
						}
						
					}
					else if (event.type === "mouseleave") {
						
						$(this).removeAttr("data-html").attr({"data-position":$(this).attr("data-position-backup")});
						
					}
			
				}
				
			);
			<?php
			
		}
		
		?>
	




		<?php

		if ($diretorio !== false) {

			?>
			$(".ordenar").css({textDecoration:"underline",cursor:"pointer"}).on(
			
				"click",
				function() {

					if (   (!(resultado = $(this).text().match(new RegExp("\\((C|D)\\)$"))))   ||   (resultado[1] == "C")   ) {

						$(this).text($(this).text().replace(new RegExp(" \\(C\\)$"),"") + " (D)");
						ordenar_crescentemente = false;
					
					}
					else if (resultado[1] == "D") {

						$(this).text($(this).text().replace(new RegExp(" \\(D\\)$"),"") + " (C)");
						ordenar_crescentemente = true;
			
					}

					coluna = $(this).parent().children().index($(this));
					formato_dado = $(this).is("[formato_dado]")?$(this).attr("formato_dado"):"";
					elemento_contem_valor = $(this).is("[elemento_contem_valor]")?$(this).attr("elemento_contem_valor"):"";
	

					$(this).parents("table").find("tbody").find("tr").sort(

						function (a,b){
						
							var valor_coluna_anterior_atual = new Array($(a).find("td").eq(coluna),$(b).find("td").eq(coluna));
					
							for (var i=0;i<2;i++) {
							
								if (elemento_contem_valor !== "") {
							
									valor_coluna_anterior_atual[i] = valor_coluna_anterior_atual[i].find(elemento_contem_valor);
								
								}
								
								valor_coluna_anterior_atual[i] = valor_coluna_anterior_atual[i].val() !== ""?valor_coluna_anterior_atual[i].val():valor_coluna_anterior_atual[i].text();
							
								if (formato_dado == "numerico_brasileiro") {

									valor_coluna_anterior_atual[i] = parseFloat(valor_coluna_anterior_atual[i].replace(new RegExp("[^0-9.,]","g"),"").replace(new RegExp("\\.","g"),"").replace(",","."));
								
								}
								else if (formato_dado == "numerico_americano") {
								
									valor_coluna_anterior_atual[i] = parseFloat(valor_coluna_anterior_atual[i].replace(new RegExp("[^0-9.,]","g"),""));
								
								}
								else if (formato_dado == "data_brasileiro") {
								
									valor_coluna_anterior_atual[i] = valor_coluna_anterior_atual[i].replace(new RegExp("-","g"),"/").split("/");
									valor_coluna_anterior_atual[i] = new Date(valor_coluna_anterior_atual[i][2] + "/" + valor_coluna_anterior_atual[i][1] + "/" + valor_coluna_anterior_atual[i][0]).getTime();
								
								}
								else if (formato_dado == "data_americano") {
								
									valor_coluna_anterior_atual[i] = valor_coluna_anterior_atual[i].replace(new RegExp("-","g"),"/");
									valor_coluna_anterior_atual[i] = new Date(valor_coluna_anterior_atual[i]).getTime();
								
								}
								
								else if (formato_dado == "hora_data_brasileiro") {
								
									valor_coluna_anterior_atual[i] = valor_coluna_anterior_atual[i].replace(new RegExp("[^0-9 -/:]","g"),"").replace(new RegExp("-","g"),"/").split(" ");
									valor_coluna_anterior_atual[i][0] = valor_coluna_anterior_atual[i][0].split(":");
									valor_coluna_anterior_atual[i][1] = valor_coluna_anterior_atual[i][1].split("/");
									valor_coluna_anterior_atual[i] = new Date(valor_coluna_anterior_atual[i][1][2] + "/" + valor_coluna_anterior_atual[i][1][1] + "/" + valor_coluna_anterior_atual[i][1][0] + " " + valor_coluna_anterior_atual[i][0][0] + ":" + valor_coluna_anterior_atual[i][0][1] + ":" + valor_coluna_anterior_atual[i][0][2]).getTime();
								
								}								
								
								else if (formato_dado == "string") {
								
									valor_coluna_anterior_atual[i] = $.trim(valor_coluna_anterior_atual[i]).toLowerCase();
								
								}
									
								//https://stackoverflow.com/questions/30314447/how-do-you-test-for-nan-in-javascript
								if (valor_coluna_anterior_atual[i] !== valor_coluna_anterior_atual[i]) {
								
									valor_coluna_anterior_atual[i] = -99999;
								
								}
								
							}
							
							if (ordenar_crescentemente) {
							
								return (valor_coluna_anterior_atual[0] < valor_coluna_anterior_atual[1])?-1:1;
							
							}
							else{
							
								return (valor_coluna_anterior_atual[0] > valor_coluna_anterior_atual[1])?-1:1;
							
							}
						
						}

					).appendTo($(this).parents("table").find("tbody"));


					$(this).parents("table").find(".tooltip").popup();
			
				}
				
			);
			<?php
		
		}
		
		?>




	
		
		$(".tooltip").popup();
		
		$(".checkbox").checkbox({
			
			onChange: function() {
				
				if (typeof relogio_checkbox !== "undefined") {
					
					window.clearTimeout(relogio_checkbox);
					
				}
				
				relogio_checkbox = window.setTimeout(
				
					function() {
					
						if ($(".checkbox_selecionar_arquivo_diretorio.checked").length > 0) {
							
							$("#botao_remover_arquivos_diretorios").css({display:"inline-block"});
							$("#botao_copiar_mover_arquivos_diretorios").css({display:"inline-block"});
							$("#exibir_arquivos_diretorios_selecionados").css({display:"inline-block"}).html('<i class="folder icon yellow"></i> <span style="color:#fbbd08;">' + $("tr").has(".checkbox_selecionar_arquivo_diretorio.checked").has(".folder").length + '</span> &nbsp;&nbsp;&nbsp; <i class="file outline icon green"></i> <span style="color:#21ba45;">' + $("tr").has(".checkbox_selecionar_arquivo_diretorio.checked").has(".file").length + '</span>');
							
							if (   ($(".checkbox_selecionar_arquivo_diretorio.checked").length > 1)   ||   (   ($(".checkbox_selecionar_arquivo_diretorio.checked").length == 1)   &&   ($(".checkbox_selecionar_arquivo_diretorio.checked").parents("tr").is("[tipo='diretório']"))   )   ) {
								
								$("#botao_zip_arquivos_diretorios").css({display:"inline-block"});
								
							}
							else{
								
								$("#botao_zip_arquivos_diretorios").css({display:"none"});
								
							}
							
							if (   ($(".checkbox_selecionar_arquivo_diretorio.checked").length === 1)   &&   ($(".checkbox_selecionar_arquivo_diretorio.checked").parents("tr").attr("tipo") === "arquivo")   &&   ($(".checkbox_selecionar_arquivo_diretorio.checked").parents("tr").find("input[name='selecionar_arquivo_diretorio']").val().match(new RegExp("\\.zip$")))   ) {
								
								$("#botao_unzip_arquivos_diretorios").css({display:"inline-block"});
								$("#botao_zip_arquivos_diretorios").css({display:"none"});
								
							}
							else{
								
								$("#botao_unzip_arquivos_diretorios").css({display:"none"});
								
							}
						
						}
						else{
						
							$("#botao_remover_arquivos_diretorios").css({display:"none"});
							$("#botao_zip_arquivos_diretorios").css({display:"none"});
							$("#botao_unzip_arquivos_diretorios").css({display:"none"});
							$("#botao_copiar_mover_arquivos_diretorios").css({display:"none"});
							$("#exibir_arquivos_diretorios_selecionados").css({display:"none"});
						
						}
						
						$(".linha_possui_checkbox_checked").has(".checkbox_selecionar_arquivo_diretorio:not(.checked)").removeClass("linha_possui_checkbox_checked");
						$("tr").has(".checkbox_selecionar_arquivo_diretorio.checked").addClass("linha_possui_checkbox_checked");
						
					},
					100
					
				);
				
			}		
		
		});
		
		$(".checkbox_selecionar_arquivo_diretorio").on(
		
			"click",
			function(event) {
		
				$(".checkbox_controla_todos").checkbox("uncheck");
				
				if (   (event.shiftKey)   &&   ($(this).is(".checked"))   ) {
				
					if ($(".checkbox_selecionar_arquivo_diretorio.checked").length > 1) {
						
						var primeiro_checkbox_selecionado = $(".checkbox_selecionar_arquivo_diretorio.checked:first");
						var ultimo_checkbox_selecionado = $(".checkbox_selecionar_arquivo_diretorio.checked:last");
						
						var encontrou_primeiro_checkbox_selecionado = false;
						
						$(".checkbox_selecionar_arquivo_diretorio").each(
						
							function() {
								
								if (encontrou_primeiro_checkbox_selecionado == false) {
									
									if ($(this).is(primeiro_checkbox_selecionado)) {
									
										encontrou_primeiro_checkbox_selecionado = true;
										
									}
									
								}
								else{
									
									if ($(this).is(ultimo_checkbox_selecionado)) {
										
										return false;
										
									}
									else{
										
										$(this).checkbox("check");
										
									}
									
								}
								
							}

						);
						
					}					
				
				}
			
			}
		
		);
		
		
		
		
		
		
		
		
		caracteres_pressionados = "";
		
		$(window.document).on(
		
			"keydown",
			function(event) {
				
				if ($(":focus").length === 0) {
					
					var temp42 = String.fromCharCode(event.which);
					
					if (temp42.match(new RegExp("[a-z0-9]","i"))) {
						
						caracteres_pressionados += temp42;
						
						//console.log("caracteres_pressionados",caracteres_pressionados);
						
						$("#exibir_caracteres_pressionados").css({display:"inline-block"}).find("span").text(caracteres_pressionados);
						
						if (typeof relogio_limpar_caracteres_pressionados !== "undefined") {
							
							window.clearTimeout(relogio_limpar_caracteres_pressionados);
							
						}
						
						relogio_limpar_caracteres_pressionados = window.setTimeout(
						
							function() {
								
								caracteres_pressionados = "";
								$("#exibir_caracteres_pressionados").css({display:"none"});
							
							},
							500
							
						);
						
						$(".encontrou_caracteres_pressionados").removeClass("encontrou_caracteres_pressionados");
						
						ja_rolou_para_elemento = false;
						
						$(".input_nome_arquivo_diretorio").each(
						
							function() {
							
								if ($.trim($(this).val()).toLowerCase().indexOf(caracteres_pressionados.toLowerCase()) === 0) {
									
									$(this).parents("tr").addClass("encontrou_caracteres_pressionados");
									
									if (ja_rolou_para_elemento === false) {
									
										$("html,body").stop(true,false).animate({scrollTop: $(this).offset().top - 64},100);
										
										
										if (typeof relogio_remover_classe_encontrou_caracteres_pressionados !== "undefined") {
											
											window.clearTimeout(relogio_remover_classe_encontrou_caracteres_pressionados);
											
										}
										
										relogio_remover_classe_encontrou_caracteres_pressionados = window.setTimeout(
										
											function() {
												
												$(".encontrou_caracteres_pressionados").removeClass("encontrou_caracteres_pressionados");
												
											},
											2000
										
										);
										
									}
									
									ja_rolou_para_elemento = true;
									
								}
								
							}
						
						);
		
					}
					
				}
			
			}
		
		);
		
		
		
		
		
	});
	
	
	function zip_arquivos_diretorios() {
		
		if (   ($(".checkbox_selecionar_arquivo_diretorio.checked").length > 1)   ||   (   ($(".checkbox_selecionar_arquivo_diretorio.checked").length == 1)   &&   ($(".checkbox_selecionar_arquivo_diretorio.checked").parents("tr").is("[tipo='diretório']"))   )   ) {

			var arquivos_diretorios_a_serem_zipados = new Array();
			
			$("input[name='selecionar_arquivo_diretorio']:checked").each(
			
				function() {
			
					arquivos_diretorios_a_serem_zipados.push($(this).val());
					
				}
			
			);
			
		
		
	
			$("#janela_mensagem").find(".actions").css({display:"none"});
			$("#janela_mensagem").find(".header").html('<i class="trash alternate icon"></i> Zipando');
			$("#janela_mensagem").find(".content").html('Aguarde enquanto o arquivo zipado é criado...');
			$("#janela_mensagem").modal("show");
	
			$.ajax({
					
				method:"POST",
				url:window.location.href,
				data:{acao:"zip",diretorio_arquivo:diretorio_arquivo,arquivos_diretorios_a_serem_zipados:JSON.stringify(arquivos_diretorios_a_serem_zipados)}
				
			}).done(function(data) {
					
				try{
				
					data = JSON.parse(data);
				
					json_esta_correto = true;
				
				}
				catch(e) {
					
					erro_json = e;
					
					json_esta_correto = false;
				
				}

				if (json_esta_correto === false) {
			
					$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro PQ32');	
					$("#janela_mensagem").find(".content").html(erro_json);
				
				}
				else{

					if (typeof data["sucesso"] !== "undefined") {
					
						$("#janela_mensagem").find(".header").html('<i class="check icon"></i> Sucesso');	
						$("#janela_mensagem").find(".content").html(data["sucesso"]);	
						
						window.open(window.location.href.split("?")[0] + "?acao=baixar_arquivo_zipado_e_remover&arquivo_zipado=" + data["arquivo_zipado"]);

					}
					else if (typeof data["erro"] !== "undefined") {
			
						$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro');	
						$("#janela_mensagem").find(".content").html(data["erro"]);
			
					}
					else{

						$("#janela_mensagem").find(".header").html('<i class="question circle icon"></i> Erro UW59');	
						$("#janela_mensagem").find(".content").html('Confira o código-fonte deste arquivo para verificar o motivo do erro.');

					}
					
				}

			}).fail(function(data) {
				
				console.log(data);
			
				$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro MP24');	
				$("#janela_mensagem").find(".content").html('Confira se você está conectado à internet, caso contrário verifique erros na aba Console e Network.');
				
			}).always(function() {
				
				$("#janela_mensagem").find(".actions").css({display:""}).find("button").get(0).focus();
				$("#janela_mensagem").modal("refresh");
				
			});			
			
			
			
			
			
			
		}
		else{
			
			alert("Você deve selecionar mais do que um arquivo um pelo menos um diretório.");
			
		}
		
	}
	
	
	
	function unzip_arquivos_diretorios() {
		
		if ($("input[name='selecionar_arquivo_diretorio']:checked").length !== 1) {
			
			alert("Você deve selecionar apenas um arquivo.");
			
		}
		else{
			
			var arquivo_a_ser_unzipado = $("input[name='selecionar_arquivo_diretorio']:checked").val();
			
			
		
		
	
			$("#janela_mensagem").find(".actions").css({display:"none"});
			$("#janela_mensagem").find(".header").html('<i class="trash alternate icon"></i> Extraindo');
			$("#janela_mensagem").find(".content").html('Aguarde enquanto o arquivo é extraído...');
			$("#janela_mensagem").modal("show");
	
			deu_tudo_certo = false;
	
			$.ajax({
					
				method:"POST",
				url:window.location.href,
				data:{acao:"unzip",diretorio_arquivo:diretorio_arquivo,arquivo_a_ser_unzipado:arquivo_a_ser_unzipado}
				
			}).done(function(data) {
					
				try{
				
					data = JSON.parse(data);
				
					json_esta_correto = true;
				
				}
				catch(e) {
					
					erro_json = e;
					
					json_esta_correto = false;
				
				}

				if (json_esta_correto === false) {
			
					$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro YE43');	
					$("#janela_mensagem").find(".content").html(erro_json);
				
				}
				else{

					if (typeof data["sucesso"] !== "undefined") {
					
						$("#janela_mensagem").find(".header").html('<i class="check icon"></i> Sucesso');	
						$("#janela_mensagem").find(".content").html(data["sucesso"]);	
						
						deu_tudo_certo = true;
				
					}
					else if (typeof data["erro"] !== "undefined") {
			
						$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro');	
						$("#janela_mensagem").find(".content").html(data["erro"]);
			
					}
					else{

						$("#janela_mensagem").find(".header").html('<i class="question circle icon"></i> Erro BX31');	
						$("#janela_mensagem").find(".content").html('Confira o código-fonte deste arquivo para verificar o motivo do erro.');

					}
					
				}

			}).fail(function(data) {
				
				console.log(data);
			
				$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro MP24');	
				$("#janela_mensagem").find(".content").html('Confira se você está conectado à internet, caso contrário verifique erros na aba Console e Network.');
				
			}).always(function() {			
				
				if (deu_tudo_certo === true) {
			
					$("#janela_mensagem").find(".actions").find(".fechar").html('Atualizar página');

					$("#janela_mensagem").modal({
					
						onHide:function() {
							
							window.location.reload(true);
							
						}
					
					});
					
				}
				else{
					
					$("#janela_mensagem").find(".actions").find(".fechar").html('Atualizar página por conta de erro na ação');

					$("#janela_mensagem").modal({
					
						onHide:function() {
							
							window.location.reload(true);
							
						}
					
					});					
					
				}
			
				$("#janela_mensagem").find(".actions").css({display:""}).find("button").get(0).focus();
				$("#janela_mensagem").modal("refresh");
				
			});			
			
			
			
			
			
			
		}
		
	}	
	
	
	
	function visualizar_arquivo_diretorio(diretorio_arquivo) {
		
		//window.open(window.location.href.split("?")[0].replace(new RegExp("[^/]+$"),""));
		window.open(url_diretorio_root + diretorio_arquivo.replace(diretorio_root,""));
		
	}
	
	function renomear_arquivo_diretorio(nome_arquivo_diretorio,arquivo_diretorio,tipo,elemento) {

		nome_arquivo_diretorio = $.trim(nome_arquivo_diretorio);
		
		if (resultado = nome_arquivo_diretorio.match(new RegExp("([^a-z0-9_ .\-])","i"))) {
			
			alert("O novo nome fornecido para este arquivo/diretório possui caracter(es) inválido(s) como \"" + resultado[1] + "\".");
			
			elemento.val(elemento.parents("tr").find("input[name='selecionar_arquivo_diretorio']").val().replace(new RegExp("/+$"),"").replace(new RegExp("^.+/"),""));
			
			return false;
			
		}
		
		if (nome_arquivo_diretorio !== arquivo_diretorio) {
	


			$("#janela_mensagem").find(".actions").css({display:"none"});
			$("#janela_mensagem").find(".header").html('<i class="edit icon"></i> Renomeando');
			$("#janela_mensagem").find(".content").html('Aguarde enquanto o arquivo/diretório é renomeado...');
			$("#janela_mensagem").modal("show");
	
			var deu_tudo_certo = false;
	
			$.ajax({
					
				method:"POST",
				url:window.location.href,
				data:{acao:"renomear",nome_atual_diretorio_arquivo:diretorio_arquivo + arquivo_diretorio,novo_nome_diretorio_arquivo:diretorio_arquivo + nome_arquivo_diretorio}
				
			}).done(function(data) {
					
				try{
				
					data = JSON.parse(data);
				
					json_esta_correto = true;
				
				}
				catch(e) {
					
					erro_json = e;
					
					json_esta_correto = false;
				
				}

				if (json_esta_correto === false) {
			
					$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro PW30');	
					$("#janela_mensagem").find(".content").html(erro_json);
				
				}
				else{

					if (typeof data["sucesso"] !== "undefined") {
					
						$("#janela_mensagem").find(".header").html('<i class="check icon"></i> Sucesso');	
						$("#janela_mensagem").find(".content").html(data["sucesso"]);

						elemento.parents("tr").find(".input_nome_arquivo_diretorio").val(nome_arquivo_diretorio);
						elemento.val(nome_arquivo_diretorio);
						
						elemento.parents("tr").find("input[name='selecionar_arquivo_diretorio']").val(diretorio_arquivo + nome_arquivo_diretorio);
						
						elemento.parents("tr").find(".hora_data_ultima_modificacao").text("### (foi renomeado)");
						elemento.parents("tr").find(".hora_data_ultimo_acesso").text("### (foi renomeado)");
						
						deu_tudo_certo = true;
						
					}
					else if (typeof data["erro"] !== "undefined") {
			
						$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro');	
						$("#janela_mensagem").find(".content").html(data["erro"]);
			
					}
					else{

						$("#janela_mensagem").find(".header").html('<i class="question circle icon"></i> Erro BV43');	
						$("#janela_mensagem").find(".content").html('Confira o código-fonte deste arquivo para verificar o motivo do erro.');

					}
					
				}

			}).fail(function(data) {
				
				console.log(data);
			
				$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro KA32');	
				$("#janela_mensagem").find(".content").html('Confira se você está conectado à internet, caso contrário verifique erros na aba Console e Network.');
				
			}).always(function() {
				
				if (deu_tudo_certo === false) {
					
					$("#janela_mensagem").find(".actions").find(".fechar").html('Atualizar página por conta de erro na ação');

					$("#janela_mensagem").modal({
					
						onHide:function() {
							
							window.location.reload(true);
							
						}
					
					});	

				}
				
				$("#janela_mensagem").find(".actions").css({display:""}).find("button").get(0).focus();
				$("#janela_mensagem").modal("refresh");
				
			});	






			
			
			
		}
	
	}
	
	
	



	function copiar_mover_arquivos_diretorios(tipo_acao) {

		var destino_copiar_mover_arquivos_diretorios = prompt("Para onde você deseja " + tipo_acao + " o(s) arquivo(s)/diretório(s) selecionado(s):",diretorio_arquivo);
		
		if (   (destino_copiar_mover_arquivos_diretorios === null)   ||   (destino_copiar_mover_arquivos_diretorios === "")   ) {
			
			alert("Ação cancelada.");
			return false;
			
		}
		else if (destino_copiar_mover_arquivos_diretorios.replace(new RegExp("/+$"),"") === diretorio_arquivo.replace(new RegExp("/+$"),"")) {
			
			alert("O destinio para onde você deseja " + tipo_acao + " o(s) arquivo(s)/diretório(s) selecionado(s) (\"" + destino_copiar_mover_arquivos_diretorios + "\") é o mesmo em que se encontra(m) (\"" + diretorio_arquivo + "\").");
			return false;
			
		}
		else if (resultado = destino_copiar_mover_arquivos_diretorios.match(new RegExp("[^a-z/-_0-9]","gi"))) {
			
			alert("O destinio para onde você deseja " + tipo_acao + " o(s) arquivo(s)/diretório(s) selecionado(s) (\"" + destino_copiar_mover_arquivos_diretorios + "\") possui caracter(es) inválido(s) (\"" + resultado + "\").");
			return false;
			
		}
		
		
		diretorios_arquivos = new Array();
		
		$('input[name=selecionar_arquivo_diretorio]:checked').each(
		
			function(){
				
				diretorios_arquivos.push($(this).val());
				
			}
			
		);
		
		
		
		$("#janela_mensagem").find(".actions").css({display:"none"});
		$("#janela_mensagem").find(".header").html('<i class="copy icon"></i> Copiando/Movendo');
		$("#janela_mensagem").find(".content").html('Aguarde enquanto os arquivo(s)/diretório(s) é/são copiado(s)/movido(s)...');
		$("#janela_mensagem").modal("show");

		var deu_tudo_certo = false;

		$.ajax({
				
			method:"POST",
			url:window.location.href,
			data:{acao:tipo_acao,diretorio_arquivo:diretorio_arquivo,diretorios_arquivos:JSON.stringify(diretorios_arquivos),destino_copiar_mover_arquivos_diretorios:destino_copiar_mover_arquivos_diretorios}
			
		}).done(function(data) {
				
			try{
			
				data = JSON.parse(data);
			
				json_esta_correto = true;
			
			}
			catch(e) {
				
				erro_json = e;
				
				json_esta_correto = false;
			
			}

			if (json_esta_correto === false) {
		
				$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro PQ32');	
				$("#janela_mensagem").find(".content").html(erro_json);
			
			}
			else{

				if (typeof data["sucesso"] !== "undefined") {
				
					$("#janela_mensagem").find(".header").html('<i class="list ol icon"></i> Resultado');	
					$("#janela_mensagem").find(".content").html(data["sucesso"]);	

					/*
					if (tipo_acao === "mover") {

						for (var i=0;i<diretorios_arquivos.length;i++) {

							$("input[name='selecionar_arquivo_diretorio']").each(
							
								function() {
									
									if ($(this).val() === diretorios_arquivos[i]) {
										
										$(this).parents("tr").remove();
										
									}
									
								}
								
							);
							
						}
						
					}
					*/
					
					if (data["sucesso"].indexOf("ERRO_ERRO_ERRO") === -1) {
						
						deu_tudo_certo = true;
						
					}						
				
				}
				else if (typeof data["erro"] !== "undefined") {
		
					$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro');	
					$("#janela_mensagem").find(".content").html(data["erro"]);
		
				}
				else{

					$("#janela_mensagem").find(".header").html('<i class="question circle icon"></i> Erro KL12');	
					$("#janela_mensagem").find(".content").html('Confira o código-fonte deste arquivo para verificar o motivo do erro.');

				}
				
			}

		}).fail(function(data) {
			
			console.log(data);
		
			$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro MP24');	
			$("#janela_mensagem").find(".content").html('Confira se você está conectado à internet, caso contrário verifique erros na aba Console e Network.');
			
		}).always(function() {
		
			if (deu_tudo_certo === true) {
			
				$("#janela_mensagem").find(".actions").find(".fechar").html('Acessar diretório de destino');

				$("#janela_mensagem").modal({
				
					onHide:function() {
						
						window.location.href = "<?php echo $url_deste_script; ?>?diretorio_arquivo=" + destino_copiar_mover_arquivos_diretorios;
						
					}
				
				});				
				
			}
			else{
			
				$("#janela_mensagem").find(".actions").find(".fechar").html('Atualizar página - com erro(s)');

				$("#janela_mensagem").modal({
				
					onHide:function() {
						
						window.location.reload(true);
						
					}
				
				});	
			
			}
		
			$("#janela_mensagem").find(".actions").css({display:""}).find("button").get(0).focus();
			$("#janela_mensagem").modal("refresh");			
		
		});

	}






	
	function criar_arquivo_diretorio(tipo_arquivo_diretorio) {
	
		/*
		var tipo_arquivo_diretorio = prompt("Digite o que deseja criar (arquivo/diretório):","diretório");
		
		if (   (tipo_arquivo_diretorio === null)   ||   (tipo_arquivo_diretorio === "")   ) {
			
			alert("Ação cancelada.");
			return false;
			
		}
		else if (   (tipo_arquivo_diretorio !== "arquivo")   &&   (tipo_arquivo_diretorio !== "diretório")   ) {
			
			alert("O que você digitou (\"" + tipo_arquivo_diretorio + "\") é inválido.");
			return false;
			
		}
		*/
		
		
		var nome_arquivo_diretorio = prompt("Digite o nome do " + tipo_arquivo_diretorio + " que será criado em \"<?php echo $diretorio_arquivo ?>\":","");
		
		if (   (nome_arquivo_diretorio === null)   ||   (nome_arquivo_diretorio === "")   ) {
			
			alert("Ação cancelada.");
			return false;
			
		}
		else if (nome_arquivo_diretorio.length < 2) {
			
			alert("O nome do " + tipo_arquivo_diretorio + " que você digitou (\"" + nome_arquivo_diretorio + "\") deve possuir mais que 1 caracter.");
			return false;
			
		}
		else if (   (   (tipo_arquivo_diretorio === "arquivo")   &&   (resultado = nome_arquivo_diretorio.match(new RegExp("[^a-z-_0-9.]","gi")))   )   ||   (   (tipo_arquivo_diretorio === "diretório")   &&   (resultado = nome_arquivo_diretorio.match(new RegExp("[^a-z-_0-9]","gi")))   )   ) {
			
			alert("O nome do " + tipo_arquivo_diretorio + " que você digitou (\"" + nome_arquivo_diretorio + "\") possui caracter(es) inválido(s) (\"" + resultado + "\").");
			return false;
			
		}
		else if (   (tipo_arquivo_diretorio === "arquivo")   &&   (!nome_arquivo_diretorio.match(new RegExp("\\.[^.]{2,8}$")))   ) {
			
			alert("O nome do " + tipo_arquivo_diretorio + " que você digitou (\"" + nome_arquivo_diretorio + "\") deve possuir uma extensão contendo entre 2 e 8 caracteres.");
			return false;
			
		}


		
		$("#janela_mensagem").find(".actions").css({display:"none"});
		$("#janela_mensagem").find(".header").html('<i class="trash alternate icon"></i> Criando');
		$("#janela_mensagem").find(".content").html('Aguarde enquanto o arquivo/diretório é criado...');
		$("#janela_mensagem").modal("show");

		var deu_tudo_certo = false;
	
		$.ajax({
				
			method:"POST",
			url:window.location.href,
			data:{acao:"criar",diretorio_arquivo:diretorio_arquivo + nome_arquivo_diretorio,tipo_arquivo_diretorio:tipo_arquivo_diretorio}
			
		}).done(function(data) {
				
			try{
			
				data = JSON.parse(data);
			
				json_esta_correto = true;
			
			}
			catch(e) {
				
				erro_json = e;
				
				json_esta_correto = false;
			
			}

			if (json_esta_correto === false) {
		
				$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro PQ32');	
				$("#janela_mensagem").find(".content").html(erro_json);
			
			}
			else{

				if (typeof data["sucesso"] !== "undefined") {
				
					$("#janela_mensagem").find(".header").html('<i class="check icon"></i> Sucesso');	
					$("#janela_mensagem").find(".content").html(data["sucesso"]);	

					deu_tudo_certo = true;

				}
				else if (typeof data["erro"] !== "undefined") {
		
					$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro');	
					$("#janela_mensagem").find(".content").html(data["erro"]);
		
				}
				else{

					$("#janela_mensagem").find(".header").html('<i class="question circle icon"></i> Erro UW59');	
					$("#janela_mensagem").find(".content").html('Confira o código-fonte deste arquivo para verificar o motivo do erro.');

				}
				
			}

		}).fail(function(data) {
			
			console.log(data);
		
			$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro MP24');	
			$("#janela_mensagem").find(".content").html('Confira se você está conectado à internet, caso contrário verifique erros na aba Console e Network.');
			
		}).always(function() {
			
			if (deu_tudo_certo) {
					
				if (tipo_arquivo_diretorio === "arquivo") {

					$("#janela_mensagem").find(".actions").find(".fechar").html('Atualizar página');

					$("#janela_mensagem").modal({
					
						onHide:function() {
							
							window.location.reload(true);
							
						}
					
					});

				}
				else if (tipo_arquivo_diretorio === "diretório") {			
					
					$("#janela_mensagem").find(".actions").find(".fechar").html('Acessar diretório criado');
			
					$("#janela_mensagem").modal({
					
						onHide:function() {
							
							window.location.href = url_deste_script + '?diretorio_arquivo=' + diretorio_arquivo.replace(new RegExp("/+$","g"),"") + '/' + nome_arquivo_diretorio;
							
						}
					
					});	

				}

			}
			
			$("#janela_mensagem").find(".actions").css({display:""}).find("button").get(0).focus();
			$("#janela_mensagem").modal("refresh");
			
		});

	}
	
	function remover_arquivo_diretorio(diretorios_arquivos,elemento) {
		
		var temp = prompt("Digite SIM para que o(s) arquivo(s)/diretório(s) abaixo seja(m) removido(s):\n\n" + diretorios_arquivos.join("\n") + "\n\n");
	
		if (temp === "SIM") {
			
	

			$("#janela_mensagem").find(".actions").css({display:"none"});
			$("#janela_mensagem").find(".header").html('<i class="trash alternate icon"></i> Removendo');
			$("#janela_mensagem").find(".content").html('Aguarde enquanto o(s) arquivo(s)/diretório(s) é/são removido(s)...');
			$("#janela_mensagem").modal("show");
	
			var deu_tudo_certo = false;
	
			$.ajax({
					
				method:"POST",
				url:window.location.href,
				data:{acao:"remover",diretorio_arquivo:diretorio_arquivo,diretorios_arquivos:JSON.stringify(diretorios_arquivos)}
				
			}).done(function(data) {
					
				try{
				
					data = JSON.parse(data);
				
					json_esta_correto = true;
				
				}
				catch(e) {
					
					erro_json = e;
					
					json_esta_correto = false;
				
				}

				if (json_esta_correto === false) {
			
					$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro PQ32');	
					$("#janela_mensagem").find(".content").html(erro_json);
				
				}
				else{

					if (typeof data["sucesso"] !== "undefined") {
					
						$("#janela_mensagem").find(".header").html('<i class="list ol icon"></i> Resultado');	
						$("#janela_mensagem").find(".content").html(data["sucesso"]);	

						for (var i=0;i<diretorios_arquivos.length;i++) {

							$("input[name='selecionar_arquivo_diretorio']").each(
							
								function() {
									
									if ($(this).val() === diretorios_arquivos[i]) {
										
										$(this).parents("tr").remove();
										
									}
									
								}
								
							);
							
						}
							
						if (data["sucesso"].indexOf("ERRO_ERRO_ERRO") === -1) {
							
							deu_tudo_certo = true;
							
						}

					}
					else if (typeof data["erro"] !== "undefined") {
			
						$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro');	
						$("#janela_mensagem").find(".content").html(data["erro"]);
			
					}
					else{

						$("#janela_mensagem").find(".header").html('<i class="question circle icon"></i> Erro KL12');	
						$("#janela_mensagem").find(".content").html('Confira o código-fonte deste arquivo para verificar o motivo do erro.');

					}
					
				}

			}).fail(function(data) {
				
				console.log(data);
			
				$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro MP24');	
				$("#janela_mensagem").find(".content").html('Confira se você está conectado à internet, caso contrário verifique erros na aba Console e Network.');
				
			}).always(function() {
				
				if (deu_tudo_certo === false) {
					
					$("#janela_mensagem").find(".actions").find(".fechar").html('Atualizar página - com erro(s)');
						
					$("#janela_mensagem").modal({
					
						onHide:function() {
							
							window.location.reload(true);
							
						}
					
					});
					
				}
				
				$("#janela_mensagem").find(".actions").css({display:""}).find("button").get(0).focus();
				$("#janela_mensagem").modal("refresh");			
			
			});


	
			
		}
		else{
			
			alert("Ação cancelada.");
			
		}
		
	}
	
	
	
	
	
	
	
	
		
		
		
		
		
	<?php
	
	if ($diretorio !== false) {
		
		?>
		var qntd_arquivos_enviados_mesmo_tempo = 10;
		
		function enviar_arquivo(indice_arquivo) {
	
			if (indice_arquivo >= qntd_arquivos_a_serem_enviados) {
				
				return false;
				
			}
	
			$("#janela_mensagem").find(".content").find("ol").append('<li><div class="ui indicating progress indice_arquivo' + indice_arquivo + '"><div class="bar"><div class="progress"></div></div><div class="label">...' + arquivo_selecionado[indice_arquivo][0]["fullPath"].substr(-50) + '</div></div></li>');
			
			$('.indice_arquivo' + indice_arquivo).progress({value:1});

			var conteudo_arquivo = new FormData();
			conteudo_arquivo.append("diretorio_arquivo","<?php echo $diretorio_arquivo; ?>");
			conteudo_arquivo.append("name",arquivo_selecionado[indice_arquivo][0]["name"]);
			conteudo_arquivo.append("fullPath",arquivo_selecionado[indice_arquivo][0]["fullPath"]);
			conteudo_arquivo.append("conteudo_arquivo",arquivo_selecionado[indice_arquivo][1]);
		
			objeto_arquivo_selecionado[indice_arquivo] = new XMLHttpRequest();

			objeto_arquivo_selecionado[indice_arquivo].onreadystatechange = function() {
		   
				if (objeto_arquivo_selecionado[indice_arquivo].readyState == 4) {
					
					if (objeto_arquivo_selecionado[indice_arquivo].status == 200) {
		   
						data = JSON.parse(objeto_arquivo_selecionado[indice_arquivo].responseText);
					
						if (typeof data["sucesso"] !== "undefined") {
					
							//$("#janela_mensagem").find(".content").find("ol").append('<li>' + data["sucesso"] + '</li>');
							$("#janela_mensagem").find(".content").find('.indice_arquivo' + indice_arquivo).parents("li").html(data["sucesso"]);
							
						}
						else if (typeof data["erro"] !== "undefined") {
						
							//$("#janela_mensagem").find(".content").find("ol").append('<li style="color:#FF0000;">' + data["erro"] + '</li>');
							$("#janela_mensagem").find(".content").find('.indice_arquivo' + indice_arquivo).parents("li").html(data["erro"]);
						
						}
						else{
							
							//alert("Erro HJ54. Informações adicionais: " + objeto_arquivo_selecionado[indice_arquivo].responseText);
							$("#janela_mensagem").find(".content").find('.indice_arquivo' + indice_arquivo).parents("li").html("Erro HJ54. Informações adicionais: " + objeto_arquivo_selecionado[indice_arquivo].responseText);
							
						}
					
					}
					else{

					
					}
					
					++qntd_arquivos_ja_enviados;
					
					$("#qntd_arquivos_ja_enviados").html(qntd_arquivos_ja_enviados);
					
					$("#janela_mensagem").modal("refresh");
					
					++indice_arquivo_atual_sendo_enviado;
					
					enviar_arquivo(indice_arquivo_atual_sendo_enviado);
					
				}
			
			};
			
			objeto_arquivo_selecionado[indice_arquivo].upload.onprogress = function(event) {

				if (   (event.lengthComputable)   &&   (event.total > 0)   ) {

					$('.indice_arquivo' + indice_arquivo).progress('set percent',Math.round(100*event.loaded/event.total));
				
				}
			
			}

			objeto_arquivo_selecionado[indice_arquivo].open("POST","<?php echo $url_deste_script; ?>",true);
			objeto_arquivo_selecionado[indice_arquivo].send(conteudo_arquivo);
			
		}
		
		function enviar_arquivos() {

			qntd_arquivos_a_serem_enviados = arquivo_selecionado.length;
			qntd_arquivos_ja_enviados = 0;

			$(window).on(
			
				"beforeunload",
				function(event) {						
			
					return "O upload ainda não foi finalizado. Deseja realmente sair?";
					
				}
			
			);

			$("#janela_mensagem").find(".actions").css({display:"none"});
			$("#janela_mensagem").find(".header").html('<i class="list ol icon"></i> Resultado');
			$("#janela_mensagem").find(".content").html('Acompanhe abaixo o envio de <span id="qntd_arquivos_ja_enviados" style="font-weight:bold;">0</span> / <span style="font-weight:bold;">' + qntd_arquivos_a_serem_enviados + '</span> arquivo(s)/diretório(s):<ol></ol>');
			$("#janela_mensagem").modal("show");



			objeto_arquivo_selecionado = new Array();
			
			for (var i=0;i<Math.min(qntd_arquivos_a_serem_enviados,qntd_arquivos_enviados_mesmo_tempo);i++) {

				indice_arquivo_atual_sendo_enviado = i;
				enviar_arquivo(indice_arquivo_atual_sendo_enviado);

			}
			
			if (typeof relogio_sistema_upload !== "undefined") {
				
				window.clearTimeout(relogio_sistema_upload);
				
			}
			
			relogio_sistema_upload = window.setInterval(
			
				function() {
					
					if (qntd_arquivos_ja_enviados === qntd_arquivos_a_serem_enviados) {
			
						$(window).off("beforeunload");
			
						$("#janela_mensagem").find(".actions").find(".fechar").html('Atualizar página');
						$("#janela_mensagem").find(".actions").css({display:""}).find("button").get(0).focus();
						
						$("#janela_mensagem").modal({
						
							onHide:function() {
								
								window.location.reload(true);
								
							}
						
						});				
					
						$("#janela_mensagem").modal("refresh");
					
						window.clearTimeout(relogio_sistema_upload);
						
					}
					
				},
				100
			
			);
			
		}

		function percorrer_diretorio(entry) {
			
			var reader = entry.createReader();
			
			reader.readEntries(function(entries) {
				
				entries.forEach(function(dir,key) {
					
					if (dir.isFile) {

						dir.file(
						
							function (file) {

								arquivo_selecionado.push(new Array(dir,file));
				
							}
							
						);
						
					}
					else{
						
						percorrer_diretorio(dir);
						
					}
				
				});
				
			});	
			
			return true;
			
		}

		$(document).ready(function() {

			$(window.document).on(
			//$(window.document).on(
			
				"dragenter dragexit dragover drop",
				function(event) {
					
					event.stopPropagation();
					event.preventDefault();
					
					if (event.type === "dragenter") {
						
						$("body").css({opacity:0.25});
						return false;
						
					}				
					
					if (event.type === "drop") {
							
						$("body").css({opacity:1});	
							
						arquivo_selecionado = new Array();	
							
						for (var i=0;i<event.originalEvent.dataTransfer.items.length;i++) {
							
							var entry = event.originalEvent.dataTransfer.items[i].webkitGetAsEntry();	
						
							if (entry.isFile) {
						
								arquivo_selecionado.push(new Array(entry,event.originalEvent.dataTransfer.files[i]));
								
							}
							else if (entry.isDirectory) {

								percorrer_diretorio(entry);

							}
						
						}
					
						window.setTimeout(
						
							function() {
						
							$(window.document).off("dragenter dragexit dragover drop");					
						
								enviar_arquivos();
								
							},
							100
							
						);
					
					}
			
				}
				
			);
			
		});	
		<?php
	
	}
	
	?>
	
	
	
	
	
	
	
	
	
	</script>
  
</head>

<body>



	<div class="ui modal small" id="janela_mensagem">

		<div class="header"></div>
		
		<div class="content">

			
			
		</div>
		
		<div class="actions">
		
			<button class="ui button red fechar">Fechar</button>
		 
		</div>
		
	</div>

	<script type="text/javascript">
	$("#janela_mensagem").modal(

		"setting",
		{
			closable:false,
			transition:"vertical flip"
			
		}
			
	).on(

		"click",
		".ui.button",
		function() {

			if ($(this).is(".fechar")) {
			
				$(this).parents(".modal").modal("hide");
			
			}
		
		}
		
	);
	</script>




	<div style="height:50px;"></div>

	<div style="font-size:14px; width:100%; position:fixed; left:0px; top:0px; z-index:5; padding:10px; background:#FFF; border-bottom:1px solid rgba(0,0,0,0.15); height:50px;">
	
		<div style="float:left;">
			
			<button class="ui icon button blue mini tooltip" data-content="Voltar" data-variation="inverted" data-position="bottom center" onclick="window.history.back();"><i class="long arrow alternate left icon"></i></button>
			
			&nbsp;
	
			<div class="ui breadcrumb">
			
				<?php
		
				$temp2445 = explode('/',$diretorio_arquivo);
				
				$temp3399 = '';
				
				for ($i=0;$i<count($temp2445) - 1;$i++) {
					
					$temp3399 .= $temp2445[$i] . '/';
				
					if ($i === count($temp2445) - 2) {
					
						if (isset($_GET["editar"])) {
						
							echo '<a class="section" href="' . $url_deste_script . '?diretorio_arquivo=' . $temp3399 . '">' . $temp2445[$i] . '</a>';
						
						}
						else{
							
							echo '<a class="section" href="javascript:temp3322 = prompt(\'Copie o diretório abaixo ou altere-o para acessá-lo:\',\'' . $temp3399 . '\'); if (   (temp3322 !== null)   &&   (temp3322 !== \'' . $temp3399 . $temp2445[count($temp2445) - 1] . '\')   ) {window.location.href = \'index.php?diretorio_arquivo=\' + encodeURIComponent(temp3322);} void(0);" style="font-weight:bold;">' . $temp2445[$i] . '</a>';
							
						}
					
					}
					else{
						
						echo '<a class="section" href="' . $url_deste_script . '?diretorio_arquivo=' . $temp3399 . '">' . $temp2445[$i] . ' <i class="right chevron icon divider"></i>';
						
					}
					
				}
				
				if (isset($_GET["editar"])) {
				
					echo '<i class="right chevron icon divider"></i> <a class="section" href="javascript:temp3322 = prompt(\'Copie o diretório do arquivo abaixo ou altere-o para editá-lo:\',\'' . $temp3399 . $temp2445[count($temp2445) - 1] . '\'); if (   (temp3322 !== null)   &&   (temp3322 !== \'' . $temp3399 . $temp2445[count($temp2445) - 1] . '\')   ) {window.location.href = \'index.php?diretorio_arquivo=\' + encodeURIComponent(temp3322) + \'&editar=\';} void(0);" style="font-weight:bold;">' . $temp2445[count($temp2445) - 1] . '</a>';
				
				}
			
				?>			
			
			</div>
	
		</div>
		
		
		<div style="float:right; margin-top:-4px;">
		
			<?php
			
			if ($diretorio !== false) {
				
				?>
	
				<div id="exibir_caracteres_pressionados" class="ui label large" style="display:none;">Buscando por: <span style="font-weight:bold; font-style:italic;"></span></div>
				
				<div id="exibir_arquivos_diretorios_selecionados" class="ui label large" style="display:none;"></div>
				
				
				
				<button id="botao_remover_arquivos_diretorios" class="ui icon button red tooltip" data-content="Remover arquivo(s)/diretório(s) selecionado(s)" data-variation="inverted" data-position="bottom center" onclick="diretorios_arquivos = new Array(); $('input[name=selecionar_arquivo_diretorio]:checked').each(function(){diretorios_arquivos.push($(this).val());}); remover_arquivo_diretorio(diretorios_arquivos);" style="display:none;"><i class="trash alternate left icon"></i></button>				
				
				<button id="botao_unzip_arquivos_diretorios" class="ui icon button yellow tooltip" data-content="Unzip arquivos/diretórios" data-variation="inverted" data-position="bottom center" onclick="unzip_arquivos_diretorios();" style="display:none;"><i class="file archive outline icon"></i></button>
				
				<button id="botao_zip_arquivos_diretorios" class="ui icon button yellow tooltip" data-content="Zip arquivos/diretórios" data-variation="inverted" data-position="bottom center" onclick="zip_arquivos_diretorios();" style="display:none;"><i class="file archive outline icon"></i></button>
		
				<div id="botao_copiar_mover_arquivos_diretorios" class="ui icon top right pointing dropdown button green dropdown_copiar_mover_arquivos_diretorios" style="display:none;">
					
					<i class="copy icon"></i>
					
					<div class="menu">
					
						<div class="item" data-value="copiar">
						
							<i class="copy outline green icon"></i>
							Copiar
							
						</div>
						
						<div class="item" data-value="mover">
						
							<i class="arrows alternate green icon"></i>
							Mover
							
						</div>
					
					</div>

				</div>				
				
				<div class="ui icon top right pointing dropdown button blue dropdown_criar_arquivo_diretorio">
				
					<i class="plus icon"></i>
					
					<div class="menu">
						
						<div class="header">Criar novo</div>
						
						<div class="item" data-value="arquivo">
						
							<i class="file green icon"></i>
							Arquivo
							
						</div>
						
						<div class="item" data-value="diretório">
						
							<i class="folder yellow icon"></i>
							Diretório
							
						</div>
						
					</div>
					
				</div>
		
				<?php
				
			}
			
			if ($arquivo !== false) {
				
				$encoding_arquivo = detectar_encoding_string($conteudo_arquivo);
				
				?>
				<div class="ui icon top right labeled pointing dropdown button dropdown_encoding">
				
					<i class="language icon"></i>
				
					<span class="text"><?php echo $encoding_arquivo; ?> (detectado)</span>
					
					<div class="menu">
						
						<div class="header">Encoding para usar quando salvar</div>
						
						<div class="item <?php echo ($encoding_arquivo === "Windows-1252"?'selected':''); ?>" data-value="Windows-1252">Windows-1252 <?php echo ($encoding_arquivo === "Windows-1252"?'(detectado)':''); ?></div>
						
						<div class="item <?php echo ($encoding_arquivo === "UTF-8"?'selected':''); ?>" data-value="UTF-8">UTF-8 <?php echo ($encoding_arquivo === "UTF-8"?'(detectado)':''); ?></div>
						
					</div>
					
				</div>				
		
				<?php
				
			}
			
			?>
			
		</div>
		
		<div style="clear:both; height:0px;"></div>
	
	</div>

	

	<?php
	
	if ($diretorio !== false) {

		?>
		<div style="padding:10px;">
		
			<?php
			
			$saida = array();
		
			if ($handle = opendir($diretorio)) {
			
				while (false !== ($entry = readdir($handle))) {

					if (   ($entry === ".")   or   ($entry === "..")   ) {
						
					}
					else if (is_file($diretorio . $entry)) {
					
						$saida['ordenar1'][] = 'arquivo';
						$saida['ordenar2'][] = mb_strtolower($entry,"UTF-8");
						
						$saida['string'][] = array(
						
							'tipo' => 'arquivo',
							'nome' => $entry,
							'diretorio_arquivo' => $diretorio . $entry,
							'tamanho' => filesize($diretorio . $entry),
							'permissoes' => fileperms($diretorio . $entry),
							'hora_data_ultima_modificacao' => filemtime($diretorio . $entry),
							'hora_data_ultimo_acesso' => fileatime($diretorio . $entry)
							
						);
						
					}
					else if (is_dir($diretorio . $entry)) {
					
						$saida['ordenar1'][] = 'diretorio';
						$saida['ordenar2'][] = mb_strtolower($entry,"UTF-8");
						
						$saida['string'][] = array(
						
							'tipo' => 'diretório',
							'nome' => $entry,
							'diretorio_arquivo' => $diretorio . $entry,
							'tamanho' => 0,
							'permissoes' => fileperms($diretorio . $entry),
							'hora_data_ultima_modificacao' => filemtime($diretorio . $entry),
							'hora_data_ultimo_acesso' => fileatime($diretorio . $entry)
							
						);
						
					}

				}
				
			}
		
		
			if (empty($saida)) {
				
				echo '
				
					<table class="ui red table">

						<thead>
						
							<tr>
								
								<th>Este diretório não possui arquivos/subdiretórios</th>
								
							</tr>
						
						</thead>
						
						<tbody>
						</tbody>
						
					</table>
						
				';
				
			}
			else{
				
				array_multisort($saida['ordenar1'],SORT_STRING,SORT_DESC,$saida['ordenar2'],SORT_STRING,SORT_ASC,$saida['string']);
				
				echo '
				
					<table class="ui red table">

						<thead>
						
							<tr>
								
								<th style="width:1%;">#</th>
								<th style="width:1%;">
								
									<div class="ui checkbox checkbox_controla_todos" onclick="if ($(this).is(\'.checked\')){$(\'.checkbox_selecionar_arquivo_diretorio\').checkbox(\'uncheck\');}else{$(\'.checkbox_selecionar_arquivo_diretorio\').checkbox(\'check\');}"><input type="checkbox" value=""></div>
								
								</th>
								<th class="ordenar" formato_dado="string" elemento_contem_valor=".input_nome_arquivo_diretorio">Nome</th>
								<th>Ações</th>
								<th class="ordenar" formato_dado="numerico_brasileiro">Tamanho</th>
								<th class="ordenar" formato_dado="hora_data_brasileiro">Data da última modificação</th>
								<th class="ordenar" formato_dado="hora_data_brasileiro">Data do último acesso</th>
								<th>Permissões</th>
								
							</tr>
						
						</thead>
						
						<tbody>
						
				';
				
				foreach ($saida['string'] as $chave1 => $valor1) {
					
					echo '
					
						<tr class="aplicar_efeito_hover_linha_tabela" style="cursor:default;" tipo="' . $saida['string'][$chave1]['tipo'] . '">
						
							<td style="font-weight:bold;">' . ($chave1 + 1) . '</td>
							<td><div class="ui checkbox checkbox_selecionar_arquivo_diretorio"><input type="checkbox" name="selecionar_arquivo_diretorio" value="' . $saida['string'][$chave1]['diretorio_arquivo'] . '"></div></td>
							<td>
							
								<i onclick="window.location.href = \'?diretorio_arquivo=\' + encodeURIComponent($(this).parents(\'tr\').find(\'input[name=selecionar_arquivo_diretorio]\').val()) + \'' . ($saida['string'][$chave1]['tipo'] === 'arquivo'?'&editar=':'') . '\';" style="cursor:pointer;" class="' . ($saida['string'][$chave1]['tipo'] === 'arquivo'?'file green':'folder yellow') . ' icon tooltip" data-content="' . ($saida['string'][$chave1]['tipo'] === 'arquivo'?'Editar arquivo':'Abrir diretório') . '" data-variation="inverted" data-position="bottom center"></i>
								
								<input class="input_nome_arquivo_diretorio" type="text" value="' . $saida['string'][$chave1]['nome'] . '" style="border:0px solid; background:transparent; width:90%;" data-content="Renomear ' . $saida['string'][$chave1]['tipo'] . '" data-variation="inverted" data-position="bottom center" onchange="renomear_arquivo_diretorio($(this).val(),$(this).parents(\'tr\').find(\'input[name=selecionar_arquivo_diretorio]\').val().replace(new RegExp(\'^.+/\'),\'\'),\'' . $saida['string'][$chave1]['tipo'] . '\',$(this));" />
								
							</td>
							<td>
							
								<i class="trash alternate icon red tooltip" style="cursor:pointer;" data-content="Remover ' . $saida['string'][$chave1]['tipo'] . '" data-variation="inverted" data-position="bottom center" onclick="remover_arquivo_diretorio(new Array($(this).parents(\'tr\').find(\'input[name=selecionar_arquivo_diretorio]\').val()),$(this));"></i>
								&nbsp; 
								<i class="eye icon blue tooltip" style="cursor:pointer;" data-content="Visualizar ' . $saida['string'][$chave1]['tipo'] . '" data-variation="inverted" data-position="bottom center" onclick="visualizar_arquivo_diretorio($(this).parents(\'tr\').find(\'input[name=selecionar_arquivo_diretorio]\').val());"></i>
								
							</td>
							<td>' . ($saida['string'][$chave1]['tipo'] === 'arquivo'?number_format($saida['string'][$chave1]['tamanho']/1024/1024,2,",",".") . ' MB':'') . '</td>
							<td class="hora_data_ultima_modificacao">' . date("H:i:s d/m/Y",$saida['string'][$chave1]['hora_data_ultima_modificacao']) . '</td>
							<td class="hora_data_ultimo_acesso">' . date("H:i:s d/m/Y",$saida['string'][$chave1]['hora_data_ultimo_acesso']) . '</td>
							<td>' . substr(sprintf('%o',$saida['string'][$chave1]['permissoes']),-4) . '</td>
							
						</tr>			
					
					';
					
				}
				
				echo '	
				
						</tbody>
					
					</table>
					
				';
				
			}
			
			?>
			
		</div>
		<?php
	
	
	}
	else if ($arquivo !== false) {
	
		?>
		<div id="editor" style="position:fixed; left:0px; top:50px; bottom:0px; right:0px;"></div>
				
		<script type="text/javascript">

		require.config({paths:{'vs':'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.21.2/min/vs/'}});
		window.MonacoEnvironment = {getWorkerUrl:() => proxy};

		let proxy = URL.createObjectURL(new Blob([`

			self.MonacoEnvironment = {baseUrl:'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.21.2/min/'};
			importScripts('https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.21.2/min/vs/base/worker/workerMain.min.js');
			
		`],{type:'text/javascript'}));

		require(["vs/editor/editor.main"],function () {
			
			//https://microsoft.github.io/monaco-editor/api/interfaces/monaco.editor.istandaloneeditorconstructionoptions.html
			editor = monaco.editor.create(document.getElementById("editor"),{
				
				value:"<?php echo carregar_arquivo_para_edicao($conteudo_arquivo); ?>",
				<?php
				
				$temp8811 = $extensao_arquivo;
				
				if ($extensao_arquivo === "js") {
					
					$temp8811 = "javascript";
					
				}
				else if ($extensao_arquivo === "php") {
				
					if (   (stripos($conteudo_arquivo,'<body') !== false)   or   (stripos($conteudo_arquivo,'<html') !== false)   ) {
					
						$temp8811 = "html";
						
					}
					else{
					
						$temp8811 = "php";
					
					}
				
				}
				
				?>
				language:"<?php echo $temp8811; ?>",
				lineNumbers:"on",
				scrollBeyondLastLine:false,
				wordWrap:"on",
				theme:"vs-dark",
				scrollbar:{
					
					verticalHasArrows:true,
					verticalScrollbarSize:25,
					arrowSize:25
					
				}
				
			});



			editor.focus();



			conteudo_editor_salvo_servidor = editor.getValue();
			
			window.editor.getModel().onDidChangeContent((event) => {
				
				if (typeof relogio_verificar_conteudo_mudou_editor !== "undefined") {
					
					window.clearTimeout(relogio_verificar_conteudo_mudou_editor);
					
				}
				
				relogio_verificar_conteudo_mudou_editor = window.setTimeout(
				
					function() {
				
						if (conteudo_editor_salvo_servidor === editor.getValue()) {
							
							$(window).off("beforeunload");
							
						}
						else{
								
							if (typeof $._data(window,"events").beforeunload === "undefined") {
							
								$(window).on(
								
									"beforeunload",
									function(e) {						
								
										return "Este arquivo foi editado porém ainda não foi salvo. Deseja realmente sair?";
										
									}
								
								);
								
							}
							
						}
					
					},
					200
					
				);
			
			});
		
		
		
			editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KEY_S,function() {
				



				$("#janela_mensagem").find(".actions").css({display:"none"});
				$("#janela_mensagem").find(".header").html('<i class="save icon"></i> Salvando');
				$("#janela_mensagem").find(".content").html('Aguarde enquanto o arquivo é salvo...');
				$("#janela_mensagem").modal("show");
			
				deu_tudo_certo = false;
			
				$.ajax({
						
					method:"POST",
					url:window.location.href,
					data:{acao:"salvar",diretorio_arquivo:"<?php echo $arquivo ?>",encoding:$(".dropdown_encoding").find(".selected").attr("data-value"),conteudo:editor.getValue()}
					
				}).done(function(data) {
						
					try{
					
						data = JSON.parse(data);
					
						json_esta_correto = true;
					
					}
					catch(e) {
						
						erro_json = e;
						
						json_esta_correto = false;
					
					}

					if (json_esta_correto === false) {
				
						$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro PQ32');	
						$("#janela_mensagem").find(".content").html(erro_json);
					
					}
					else{

						if (typeof data["sucesso"] !== "undefined") {
						
							$("#janela_mensagem").find(".header").html('<i class="check icon"></i> Sucesso');	
							$("#janela_mensagem").find(".content").html(data["sucesso"]);	

							conteudo_editor_salvo_servidor = editor.getValue();
							$(window).off("beforeunload");	

							deu_tudo_certo = true;

						}
						else if (typeof data["erro"] !== "undefined") {
				
							$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro');	
							$("#janela_mensagem").find(".content").html(data["erro"]);
				
						}
						else{

							$("#janela_mensagem").find(".header").html('<i class="question circle icon"></i> Erro BX30');	
							$("#janela_mensagem").find(".content").html('Confira o código-fonte deste arquivo para verificar o motivo do erro.');

						}
						
					}

				}).fail(function(data) {
					
					console.log(data);
				
					$("#janela_mensagem").find(".header").html('<i class="times circle icon"></i> Erro MP24');	
					$("#janela_mensagem").find(".content").html('Confira se você está conectado à internet, caso contrário verifique erros na aba Console e Network.');
					
				}).always(function() {
				
					if (deu_tudo_certo === true) {
					
						$("#janela_mensagem").modal({
						
							onHide:function() {
								
								editor.focus();
								
								$(this).modal({onHide:function() {}});
								
							}
						
						});	
						
					}
				
					$("#janela_mensagem").find(".actions").css({display:""}).find("button").get(0).focus();
					$("#janela_mensagem").modal("refresh");					
				
				});



			
			});				
			
		});
		</script>			
		<?php
		
	}
	
	?>




</body>
</html>