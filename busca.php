<?php
//a fazer:
//busca de produtos
//passar botao_id para montar sub-menu de página
if(!$_REQUEST["busca"])
{
	echo $o_ajudante->titulo("Busca","Favor escrever uma palavra ou parte dela.");
}
else
{
/*
	$sql .= " AND ";
	
	//quebra o texto da busca
	$busca_produto = explode(" ",$tira_palavras);
	
	//monta sql	
	for($i = 0; $i < count($busca_produto); $i++)
	{
		//escreve ou não o OR
		if($i >= (count($busca_produto)-1)){$OR = "";}else{$OR = " OR ";}
		$sql .= " produto_titulo LIKE '%".$busca_produto[$i]."%' ".$OR;
	}

}*/
	$o_pagina = new Pagina;
	$o_pagina->set('termo_busca',$_REQUEST["busca"]);
	$rs = $o_pagina->selecionar_busca();

	$n = mysql_num_rows($rs);

	if($n != 0)
	{
		echo $o_ajudante->titulo("Resultado de Busca","Resultados encontrados pela busca por: \"<strong>".$_REQUEST["busca"]."</strong>\".<br/><br/>","i");
	
		while($l = mysql_fetch_array($rs))
		{
			$corpo = strip_tags(substr($l["corpo"],0,100));
		
			//zebrado
			$bgcolor == "zebrado01" ? $bgcolor = "zebrado02" : $bgcolor = "zebrado01";
			
			if($l["s"] == 1)
			{
			?>
				<a class="<?=$bgcolor?>" href="<?=$_SERVER['PHP_SELF']?>?acao_noticia=detalhe&acao=noticias&noticia_id_02=<?=$l["id"]?>"><?=$l["titulo"]?>
                <?=$corpo?>
                </a>
			<?php
			}
			else
			{
			?>
				<a class="<?=$bgcolor?>" href="<?=$_SERVER['PHP_SELF']?>?botao_id=<?=$l["id"]?>&acao=paginas&pagina_id=<?=$l["id"]?>"><?=$l["titulo"]?>
				<?=$corpo?>
                </a>
			<?php
			}
		}
		

	}
	else
	{
		echo $o_ajudante->titulo("Sem registros","Nenhum registro encontrado com o termo <b>\"".$_REQUEST["busca"]."\"</b>, favor tentar outra palavra.");
	}

}
?>