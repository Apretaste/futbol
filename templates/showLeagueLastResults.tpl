<center>
<h1>{$titulo}</h1>

{space10}
<table style="text-align:center" width="100%">
	<tr>
		<th><h2>Fecha</h2></th>
    	<th><h2>Local</h2></th>
	    <th></th>
	    <th><h2>Visitante</h2></th>
	    <th colspan="3"><h2>Resultado</h2></th>
    </tr>
    {foreach $fixture as $juego}
	    {strip}
	    <tr>
	    	<td>{$juego->date|date_format:"%d/%m/%Y"}</td>
	        <td>{$juego->homeTeamName}</td>
	        <td>-</td>
	        <td>{$juego->awayTeamName}</td>
	        <td><b>{$juego->result->goalsHomeTeam}</b></td>
	        <td><b>:</b></td>
	        <td><b>{$juego->result->goalsAwayTeam}</b></td>
	    </tr>
	    {/strip}
    {/foreach}
</table>
{space15}
<table style="text-align:center;" width="100%">
	<tr>
	    <td style="" colspan="1">
		    {button href="FUTBOL LIGA {$liga->payload->id}" caption="Ver Liga" color="green" }
		</td>
	</tr>
</table>
{space10}