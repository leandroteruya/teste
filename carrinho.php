<?php
ob_start(); 
session_name("user");
session_start("user");

ini_set('display_errors', E_ALL);

if(!$_SESSION["idioma"])
{
	$_SESSION["idioma"] = "br";
}

include ("../inc/includes.php");
require_once "pag_seguro/PagSeguroLibrary.php";
require("../inc/idioma_".$_SESSION["idioma"].".php");

$o_pedido = new Pedido;
$o_produto = new Produto;
$o_produto_complemento = new Produto_complemento;
$o_pedido_produto = new Pedido_produto;
$o_monta_produto = new Monta_produto;
$o_ajudante = new Ajudante;

$id_portal = 2;//moveis
if($id_portal ==1)
{
	$portal = "pack";
}
else
{
	$portal = "moveis";
}


function carrega_mensagem($nome_pessoa, $mensagem, $id_pedido, $transaction_id, $nome_estado, $corpo_estado, $lista_produto, $data, $dados_cliente, $formas_pagamento, $nome_usuario_funcionario, $email_usuario_funcionario, $template)
{
	$o_ajudante = new Ajudante;

	//Pega Template
	$conteudo = $o_ajudante->template("../templates/".$template.""); //mensagem dos pedidos
	$lista = array(
		"[nome_pessoa]" => $nome_pessoa,
		"[mensagem]" => $mensagem,
		"[id_pedido]" => $id_pedido,
		"[data]" => $data,
		"[dados_cliente]" => $dados_cliente,
		"[informacoes_cadastro]" => $formas_pagamento,
		//"[transaction_id]" => $transaction_id,
		//"[nome_estado]" => $nome_estado,
		//"[corpo_estado]" => $corpo_estado,
		//"[nome_usuario_funcionario]" => $nome_usuario_funcionario,
		//"[email_usuario_funcionario]" => $email_usuario_funcionario,
		"[lista_produto]" => $lista_produto
	);

	$conteudo = strtr($conteudo,$lista);

	return $conteudo;
	unset($o_ajudante);
}


switch($_REQUEST['acao_carrinho'])
{
	case 'comprar':
		$redirecionar = false;
		$o_produto->set("id",$_REQUEST['_produto_id']);
		if($_REQUEST['_quantidade'] > 0)
		{
			$quantidade = $_REQUEST['_quantidade'];
		}
		else
		{
			$quantidade = 1;
		}
		if($res = $o_produto->selecionar())
		{
			foreach($res as $l)
			{
				$o_produto_complemento->set("id_produto",$l['id']);
				$o_produto_complemento->set("id",$_REQUEST['produto_selecionado']);
				if($rs = $o_produto_complemento->selecionar())
				{
					foreach($rs as $linha)
					{
						$produto_id = $l['id'];
						$complemento_id = $linha['id'];
						$nome = $l['nome'];
						$tipo = $linha['tipo'];
						$especificacao = $linha['especificacao'];
						$preco = $linha['preco'] * $quantidade;
						$desconto = $linha['desconto'];
						$produto_preco_desconto = $o_ajudante->desconto($linha["preco"],$linha["desconto"]);
					}
				}
			}
		}
		//busca se já existe o pedido session
		$o_pedido->set('session_id',session_id());
		$o_pedido->set('id_portal',$id_portal);
		if(!($rs_pedido = $o_pedido->selecionar()))
		{
			//echo "Session de pedido criada";
			$o_pedido->set('usuario_id',$_SESSION["usuario_numero"]);
			$o_pedido->set('session_id',session_id());
			$o_pedido->set('data',date("Y-m-d H:i:s"));
			$o_pedido->set('valor',$_REQUEST['preco_total']);
			$o_pedido->set('frete',$_REQUEST['preco_total']);
			$o_pedido->set('visto','n');
			$o_pedido->set('tipo_usuario',$_SESSION["tipo_usuario"]);
			$o_pedido->set('id_portal',$id_portal);
			$o_pedido->set('pedido_estado_id',1);
			if($r = $o_pedido->inserir())
			{
				$redirecionar = true;
			}

			//descobre número do pedido para inserção na tabela de pedido_produtos
			$o_pedido->set('session_id',session_id());
			$o_pedido->set('id_portal',$id_portal);
			$rs_pedido = $o_pedido->selecionar();
			foreach($rs_pedido as $linha_pedido_numero)
			{
				//insere o primeiro produto
				$_SESSION["linha_pedido_numero"] = $linha_pedido_numero["id"];
				$o_pedido_produto->set('pedido_id',$linha_pedido_numero["id"]);
				$o_pedido_produto->set('produto_id',$produto_id);
				$o_pedido_produto->set('produto_valor',$produto_preco_desconto);
				$o_pedido_produto->set('produto_quantidade',$quantidade);
				$o_pedido_produto->set('produto_valor_total',$preco);
				$o_pedido_produto->set('produto_complemento',$complemento_id);
				$r = $o_pedido_produto->inserir(); 
			}
		}
		else
		{
			$o_pedido->set('usuario_id',$_SESSION["usuario_numero"]);
			$o_pedido->set('session_id',session_id());
			$o_pedido->set('id_portal',$id_portal);
			$o_pedido->set('data',date("Y-m-d  H:i:s"));
			$o_pedido->set('valor',$_REQUEST['preco_total']);
			$o_pedido->set('frete',$_REQUEST['preco_total']);
			$o_pedido->set('visto','n');
			$o_pedido->set('pedido_estado_id',1);
			$r = $o_pedido->editar(); 

			//busca se já existe um produto igual no carrinho
			//$o_pedido_produto = new Pedido_produto;
			$o_pedido_produto->set('produto_id',$produto_id);
			$o_pedido_produto->set('produto_complemento', $complemento_id);
			$o_pedido_produto->set('pedido_id',$_SESSION["linha_pedido_numero"]);
			if($rs = $o_pedido_produto->selecionar())
			{
				$mensagem = $o_ajudante->mensagem(140);
				$mensagem = str_replace('[nome_produto]',$nome." ".$tipo,$mensagem);
			}
			else
			{
				//descobre número do pedido para inserção na tabela de pedido_produtos
				$o_pedido = new Pedido;
				$o_pedido->set('id_portal',$id_portal);
				$o_pedido->set('session_id',session_id());
				if($rs = $o_pedido->selecionar())
				{
					foreach($rs as $l)
					{
						//insere o primeiro produto
						$o_pedido_produto->set('pedido_id',$l["id"]);
						$o_pedido_produto->set('produto_id',$produto_id);
						$o_pedido_produto->set('produto_valor',$produto_preco_desconto);
						$o_pedido_produto->set('produto_quantidade',$quantidade);
						$o_pedido_produto->set('produto_valor_total',$preco);
						$o_pedido_produto->set('produto_complemento',$complemento_id);
						$r = $o_pedido_produto->inserir(); 

						//limpa frete
						$o_pedido->set('id_portal',$id_portal);
						$o_pedido->set('session_id',session_id());
						$rs = $o_pedido->editar_02();
						$redirecionar = true;
					}
				}
				else
				{}
			}
		}
		if($redirecionar)
		{
			header("Location: carrinho.php");
		}
	break;


	case 'alterar':
		for($i = 0; $i<count($_REQUEST['quantidade']); $i++)
		{
			$o_produto->set('id',$_REQUEST['produto_id'][$i]);
			if($rs = $o_produto->selecionar())
			{
				foreach($rs as $linha_produto)
				{
					$o_produto_complemento->set('id',$_REQUEST['complemento_id'][$i]);
					if($res = $o_produto_complemento->selecionar())
					{
						foreach($res as $linha_complemento)
						{
							$produto_id = $linha_produto["id"];
							$complemento_id = $linha_complemento["id"];
							if($linha_complemento["desconto"] != 0)
							{
								$preco_atualizado = $o_ajudante->desconto($linha_complemento["preco"],$linha_complemento["desconto"]);
							}
							else
							{
								$preco_atualizado = $linha_complemento["preco"];
							}
						}
					}
				}
			}
		
			if(is_numeric($_REQUEST["quantidade"][$i]) && $_REQUEST["quantidade"][$i] > 0)
			{
				//Já inclui o desconto do produto.
				$produto_valor_total = ($preco_atualizado * $_REQUEST["quantidade"][$i]);
				
				$o_pedido_produto->set('produto_id',$_REQUEST['produto_id'][$i]);
				$o_pedido_produto->set('produto_complemento',$_REQUEST['complemento_id'][$i]);
				$o_pedido_produto->set('pedido_id',$_REQUEST['pedido_id']);
				$o_pedido_produto->set('produto_valor',$preco_atualizado);
				$o_pedido_produto->set('produto_quantidade',$_REQUEST['quantidade'][$i]);
				$o_pedido_produto->set('produto_valor_total',$produto_valor_total);
				$rs = $o_pedido_produto->editar();
				$mensagem = $o_ajudante->mensagem(1);
			}
			else
			{
				$mensagem = $o_ajudante->mensagem(143);
			}
		}

		//limpa frete
		$o_pedido->set('session_id',session_id());
		$o_pedido->set('id_portal',$id_portal);
		$rs = $o_pedido->editar_02();
	break;


	case 'limpartudo':

		$o_pedido->set('id',$_REQUEST['pedido_id']);
		$rs = $o_pedido->excluir();

		$o_pedido_produto->set('pedido_id',$_REQUEST['pedido_id']);
		$rs = $o_pedido_produto->excluir();
		
		header("Location: produto.php?acao=produto&acao_produto=sub_menu");
	break;


	case 'apagar':
		//tira item do carrinho
		$o_pedido_produto->set('id',$_REQUEST['_id']);
		$o_pedido_produto->set('produto_id',$_REQUEST['produto_id']);
		$o_pedido_produto->set('produto_complemento',$_REQUEST['complemento_id']);
		$o_pedido_produto->set('pedido_id',$_REQUEST['pedido_id']);
		$rs = $o_pedido_produto->excluir();
		$mensagem = $o_ajudante->mensagem(8);
		unset($o_pedido_produto);
	break;

	case 'c_frete':
		//calcula o frete com o pagseguro
		$o_calcula_frete = new Calcula_frete;
		$o_calcula_frete->set('cep_origem', '01415000');	// TROCAR O CEP DA EMPRESA
		$o_calcula_frete->set('peso_total', $_REQUEST["peso_total"]);
		$o_calcula_frete->set('preco_total', number_format($_REQUEST["preco_total"], 2, ',', '.'));
		$o_calcula_frete->set('cep_destino', $_REQUEST["cep_01"].$_REQUEST["cep_02"]);
		$valorFrete = $o_calcula_frete->calcula();
		$valorFrete = $valorFrete["Sedex"];
	break;

	case 'fecha_orcamento':
		$o_pedido->set('session_id',session_id());
		$o_pedido->set('id_portal',$id_portal);
		if($rs = $o_pedido->selecionar())
		{
			foreach($rs as $l)
			{
				$pedido_id = $l['id'];
			}		
			$tipo_usuario = $_SESSION["tipo_usuario"];
			$o_pedido_produto->set('pedido_id', $pedido_id);
			if($rs = $o_pedido_produto->selecionar_05())
			{
				foreach($rs as $l)
				{
					$valor_total = $l['valor_total'];
				}
				$o_pedido->set('session_id',session_id());
				$o_pedido->set('usuario_id_02','101');
				$o_pedido->set('tipo_usuario',$tipo_usuario);
				$o_pedido->set('usuario_id', $_SESSION['usuario_numero']);
				$o_pedido->set('valor', $valor_total);
				$o_pedido->set('id_portal',$id_portal);
				$o_pedido->editar_09();
				session_regenerate_id();
			}
			else{die('nao foi posible selecionar_05 pedido_produto');}
		}else{die('nao foi posible selecionar o pedido');}

		$o_pedido = new Pedido;
		$o_pedido->set('id', $pedido_id);
		if($tipo_usuario == 'f')
		{
			$conteudo = $o_ajudante->template("../templates/usuario_dados.html");
		}
		else
		{
			$conteudo = $o_ajudante->template("../templates/empresa_dados.html");
		}
		if($rs = $o_pedido->selecionar_usuario_01())
		{
			foreach($rs as $l)
			{
				$valor_pedido = $l['valor'];
				$valor_pedido = $valor_pedido + ($l['ipi'] * $l['valor']);
				$nome = $l['nome_usuario_cliente'];
				$email = $l['email'];
				$data = $l['data'];
				$nome_usuario_funcionario = $l['nome_usuario_funcionario'];
				$email_usuario_funcionario = $l['email_usuario_funcionario'];
				$formas_pagamento = $l['formas_pagamento'];
				$formas_frete = $l['formas_frete'];
			}

			$o_monta_site = new Monta_site;
			$o_monta_site->set('id', $l['id']);
			$o_monta_site->set('acao_click', '10');
			$o_monta_site->set('orcamento', 'n');
			$lista_produto = $o_monta_site->monta_lista_produtos();
			unset($o_monta_site);

			//Dados do cliente
			$lista = array(
			"[nome_usuario_cliente]" => "".$l['nome_usuario_cliente']."",

			"[razao_social]" => "".$l['razao_social']."",
			"[nome_contato]" => "".$l['nome_contato']."",

			"[cpf_cnpj]" => "".$l['cpf_cnpj']."",
			"[email]" => "".$l['email']."",
			"[cep]" => "".$l['cep']."",
			"[endereco]" => "".$l['endereco']."",
			"[numero]" => "".$l['numero']."",
			"[complemento]" => "".$l['complemento']."",
			"[bairro]" => "".$l['bairro']."",
			"[uf]" => "".$l['uf']."",
			"[cidade]" => "".$l['cidade']."",
			"[telefone]" => "".$l['telefone']."",
			"[celular]" => "".$l['celular']."",
			"[fax]" => "".$l['fax'].""
			);
			$dados_cliente = strtr($conteudo,$lista);
			
			$o_configuracao = new Configuracao;
			$msg_cliente = "Sua cotação foi registrado com sucesso, um de nossos funcionários entrará em contato com você. ";
			$mensagem_mail = carrega_mensagem($nome, $msg_cliente, $pedido_id, "", "", "", $lista_produto, $data, "", "", "", "", "mensagem_cliente.html");
			if($o_ajudante->email_html($o_configuracao->site_nome()." - Notificação ",$mensagem_mail,$o_configuracao->email_contato(),$email,"../templates/template_mailing.htm"))
			{
				$texto = "email enviado com sucesso!";
			}
			
			$o_usuario = new Usuario;
			$o_usuario->set('id', 101);// envio de email pra o uusario 101
			if($rs = $o_usuario->selecionar())
			{
				foreach($rs as $l_u)
				{
					$nome_usuario_funcionario = $l_u['nome'];
					$email_usuario_funcionario = $l_u['email'];
				}
				$msg_funcionario = "Uma nova cotação foi registrado no sistema. ";
				$mensagem_mail_02 = carrega_mensagem($nome_usuario_funcionario, $msg_funcionario, $pedido_id, "", "", "", $lista_produto, $data, $dados_cliente, "", "", "", "mensagem_marketing.html");
				if($o_ajudante->email_html($o_configuracao->site_nome()." - Notificação ",$mensagem_mail_02,$o_configuracao->email_contato(),$email_usuario_funcionario,"../templates/template_mailing.htm"))
				{
					$texto = "email enviado com sucesso!";
				}
			}
			
			unset($o_usuario);
			unset($o_configuracao);
		}
		else
		{
			die('sem registro de dados');
		}
		
		
	break;
	
	default:
		$mensagem = $o_ajudante->mensagem(141);
	break;
}

	$o_pedido = new Pedido;
	$o_pedido->set('session_id',session_id());
	$o_pedido->set('id_portal',$id_portal);
	if($rs = $o_pedido->selecionar())
	{
		foreach($rs as $linha_pedido)
		{
			$pedido_id = $linha_pedido['id'];
			$nota_cliente = $linha_pedido['nota_cliente'];

			//descobre produtos do carrinho
			$o_pedido_produto = new Pedido_produto;
			$o_pedido_produto->set('pedido_id',$linha_pedido['id']);
			$o_pedido_produto->set('id',NULL);

			$peso_total = 0;
			if($rs = $o_pedido_produto->selecionar_02())
			{
				//echo mysql_num_rows($res_sql_prod);
				$cont_rows = 1;
				foreach($rs as $l)
				{
					//SUBTOTAIS DE CADA PRODUTO
					$subtotal =   $l['produto_quantidade'] * str_replace(",",".",$l['produto_valor']);
					$subtotal_f = number_format($subtotal, 2, ',', '.');//mostrar
					$sub_total_emb = $l['produto_quantidade'] * str_replace(",",".",$_SESSION['carrinho'][$indice]['prod_cartao_preco']);

					//TOTAL GERAL
					$total   +=   $subtotal;
					//$total_f = number_format($total, 2, ',', '.');

					//frete
					$t_frete = $linha_pedido['frete'];
					$t_frete =  str_replace(",",".",$t_frete); //usado para somar
					$t_frete_f = number_format($t_frete, 2, ',', '.'); //usado para mostrar
					//$total_emb += $sub_total_emb;

					//$produto_id = $l['produto_id']; //$produto_id fica com o código do produto
			 
					//zebrado
					if($bgcolor == "#eeeeee")
					{
						$bgcolor = "#ffffff";
					}
					else
					{
						$bgcolor = "#eeeeee";
					}
					//zebrado termina
					
					//Pega Template
					$conteudo = $o_ajudante->template("../templates/carrinho_lista.html");
					$peso = $l['peso'];
					$lista = array(
					"[bgcolor]" => $bgcolor,
					"[id]" => $l['pedido_produto_id'],
					"[imagem]" => $o_monta_produto->monta_imagem($l['produto_complemento'])." ".$l['nome'],
					"[lnk_produto]" => "produto.php?_categoria_id=".$l['categoria_id']."&acao=produto&acao_produto=detalhe&id=".$l['produto_id']."&complemento_id=".$l['produto_complemento']."",
					"[especificacao]" => $l['tipo'],
					"[pedido_id]" => $l['pedido_id'],
					"[produto_id]" => $l['produto_id'],
					"[produto_complemento]" => $l['produto_complemento'],
					"[produto_quantidade]" => $l['produto_quantidade'],
					"[preco_produto_f]" => number_format($l['produto_valor'], 2, ',', '.'),
					"[subtotal_f]" => $subtotal_f,
					"[peso]" => $peso,
					"[peso_total]" => $peso * $l['produto_quantidade'],
					"[cont_rows]" => "".$cont_rows.""
					);
					
					$servico_lista .= strtr($conteudo,$lista);
					//Termina Template
					
					
					$preco_produto = $linha['preco'];
					
					//peso
					$peso_sub  =  $peso * $l['produto_quantidade'];
					$peso_total = $peso_total + $peso_sub;
					$preco = number_format($l['preco'], 2, '.', ',');
					$cont_rows++;
				}//fecha while
			
				$script = "
				<script language=\"javascript\">
					window.onload = function()
					{
						document.getElementById('div_carrinho').style.display = \"block\";
						document.getElementById('div_btn').style.display = \"none\";
					}
				</script> ";
			}
			else
			{
				$script = "
					<script language=\"javascript\">
						window.onload = function()
						{
							document.getElementById('div_carrinho').style.display = \"none\";
							document.getElementById('div_btn').style.display = \"block\";
						}
					</script> ";
			}
			$peso_total_gr = $peso_total;
			$peso_total = $peso_total / 1000;

			/*if($peso_total <= 30)
			{
				$peso_ok = true;
			}
			else
			{
				$mensagem = $o_ajudante->mensagem(142);
				$peso_ok = false;
			}*/
			
			if($_REQUEST['acao_carrinho'] == 'c_frete')
			{
				$cad_frete = str_replace(",",".",$valorFrete);
				$total_geral = $total + $cad_frete;
				if(is_numeric($cad_frete) && $cad_frete > 0){
					$frete_total = number_format($cad_frete, 2, ',', '.');
				}
				else
				{
					$frete_total = 0;
				}
			}
			else
			{
				$total_geral = $total+$t_frete;
				$frete_total = $t_frete;
			}
			$total_geral = number_format($total_geral, 2, ',', '.');
		}

		if($_SESSION["acesso"] != "sim")
		{
			$url = "login.php?_destino=".$_SERVER['PHP_SELF'];
			$on_click_endereco = "";
		}
		else
		{
			//Para compra online
			/*$url = "#";
			$on_click_endereco = "popup_()";*/
			//Para Orçamento
			$url = "carrinho.php?acao_carrinho=fecha_orcamento&msg=159";
			$on_click_endereco = "";
		}
		/*if(!$peso_ok)
		{
			$url = $_SERVER['PHP_SELF'];
			$on_click_endereco = "";
			$mensagem = $o_ajudante->mensagem(142);
		}*/
	}
	else
	{
		$mensagem = $o_ajudante->mensagem(139);
		$url = $_SERVER['PHP_SELF']."?msg=";
		$on_click_endereco = "";
		$script = "
			<script language=\"javascript\">
				window.onload = function()
				{
					document.getElementById('div_carrinho').style.display = \"none\";
					document.getElementById('div_btn').style.display = \"block\";
				}
			</script> ";
	}
	if($_REQUEST['msg'])
	{
		$mensagem = $o_ajudante->mensagem($_REQUEST['msg']);
		$mensagem_02 = $o_ajudante->mensagem_02($_REQUEST['msg']);
		$mensagem_script = "
				<script language=\"javascript\" type=\"text/ecmascript\">
				$(window).load(function()
				{
						alert(\"".$mensagem_02."\");
					});
				</script>";
	}
	if(trim($mensagem) == "")
	{
		$mensagem = "";
	}

	
	//pega o template
	$conteudo = $o_ajudante->template("../templates/carrinho.html");
	//troca as variáveis
	$conteudo = str_replace('[mensagem_script]',$mensagem_script,$conteudo);
	$conteudo = str_replace('[mensagem]',$mensagem,$conteudo);
	$conteudo = str_replace('[peso_total]',$peso_total,$conteudo);
	$conteudo = str_replace('[lista]',$servico_lista,$conteudo);
	$conteudo = str_replace('[total]',$total,$conteudo);
	$conteudo = str_replace('[t_frete_f]',$frete_total,$conteudo);
	$conteudo = str_replace('[frete_total]',$frete_total,$conteudo);
	$conteudo = str_replace('[total_geral]',$total_geral,$conteudo);
	$conteudo = str_replace('[pedido_id]',$pedido_id,$conteudo);
	$conteudo = str_replace('[input_produto]',$input_produto,$conteudo);
	$conteudo = str_replace('[acao]',$_SERVER['PHP_SELF'],$conteudo);
	$conteudo = str_replace('[href_comprar]',$url,$conteudo);
	$conteudo = str_replace('[on_click_endereco]',$on_click_endereco,$conteudo);
	$conteudo = str_replace('[script]',$script,$conteudo);
	$conteudo = str_replace('[nota_cliente]',$nota_cliente,$conteudo);

	$o_menu_produto = new Menu_produto;
	$o_menu_produto->set('id_portal', $id_portal);
	
	//inicializa o template geral do site
	$o_html = new Html;
	$o_html->set('sub_menu',"".$o_menu_produto->menu_produto()."");
	$o_html->set('corpo',$conteudo);
	$o_html->set('portal',$portal);
	echo $codigo_html =  $o_html->codigo_html();
	unset($conteudo);
	unset($o_html);

	unset($o_pedido);
	unset($o_produto);
	unset($o_produto_complemento);
	unset($o_pedido_produto);
	unset($o_monta_produto);
	unset($o_ajudante);
?>