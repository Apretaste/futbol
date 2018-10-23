<style type="text/css">
  {include file="../includes/style.css"}
</style>
<center>
	<h1>Selecciona tu liga:</h1>
</center>

<table style="text-align:center;" width="100%" class="table">
	<tr>
		<th><h2>Liga</h2></th>
		<th><h2>Jornada Actual</h2></th>
		<th><h2>M&aacute;s informaci&oacute;n</h2></th>
	</tr>
	{foreach $ligas as $liga}
		{strip}
		{if true || $liga->id == 426 || $liga->id == 430 || $liga->id == 436 || $liga->id == 433 || $liga->id == 438 || $liga->id == 440}
		   <tr bgcolor="{cycle values="#f2f2f2,white"}">
		      <td style="font-weight: bold;">{$liga->name|regex_replace: "/Primera Division/":"LaLiga | LFP de España"|replace:{$liga->name|substr:-8}:""}</td>
		      <td style="">{link href="FUTBOL JORNADA {$liga->id} {$liga->currentSeason->currentMatchday}" caption="{$liga->currentSeason->currentMatchday}"}</td>
		      <td style="" colspan="1">
		      	{button href="FUTBOL LIGA {$liga->id}" caption="Ver Liga" color="green" size="small"}
		      </td>
		   </tr>
		{/if}
		{/strip}
	{/foreach}
</table>

{space10}
