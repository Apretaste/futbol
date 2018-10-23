<style type="text/css">
    {include file="../includes/style.css"}
</style>
<center>
<h1>{$liga->payload->name}</h1>
{space5}
<h2>Posiciones</h2>
{space10}
{if $tipoTorneo == 'liga'}
    <table style="text-align:center" width="100%" class="table">
        <tr>
            <th><h2>#</h2></th>
            <th><h2>Equipo</h2></th>
            <th><h2>Puntos</h2></th>
            <th><h2>PJ</h2></th>
            <th><h2>PG</h2></th>
            <th><h2>PE</h2></th>
            <th><h2>PP</h2></th>
            <th><h2>GF</h2></th>
            <th><h2>GC</h2></th>
            <th><h2>Dif</h2></th>
        </tr>
        {foreach $posicionesLiga->standings[0]->table as $position}
            <tr>
                <td>{$position->position}</td>
                <td>{link href="FUTBOL EQUIPO {$liga->payload->id} {$position->team->id}" caption="{$position->team->name}"}</td>
                <td>{$position->points}</td>
                <td>{$position->playedGames}</td>
                <td>{$position->won}</td>
                <td>{$position->draw}</td>
                <td>{$position->lost}</td>
                <td>{$position->goalsFor}</td>
                <td>{$position->goalsAgainst}</td>
                <td>{$position->goalDifference}</td>
            </tr>
        {/foreach}
    </table>
    {space10}
	<h2>Próxima jornada</h2>
	{space10}
	<table style="text-align:center" width="100%" class="table">
		<tr>
	    	<th><h2>Local</h2></th>
		    <th></th>
		    <th><h2>Visitante</h2></th>
		    <th><h2>Fecha</h2></th>
	    </tr>
	    {foreach $nextFixture as $juego}
		    {strip}
		    <tr>
		        <td>{$juego->homeTeam->name}</td>
		        <td>Vs.</td>
		        <td>{$juego->awayTeam->name}</td>
				<td>{$juego->utcDate|date_format:"%d/%m/%Y %H:%M"}</td>
		    </tr>
		    {/strip}
	    {/foreach}
	    
	</table>
{else}
	{foreach $posicionesLiga->standings as $group}
        {if $group->type=="TOTAL"}
     	<table style="text-align:center" width="100%" class="table">
         	<tr>
                <th colspan="8">{$group->group}</th>
            </tr>
            <tr>
                <th><h2>#</h2></th>
                <th><h2>Equipo</h2></th>
                <th><h2>PJ</h2></th>
                <th><h2>Puntos</h2></th>
                <th><h2>GF</h2></th>
                <th><h2>GC</h2></th>
                <th><h2>Dif</h2></th>
            </tr>
        {foreach $group->table as $team} 
            <tr>
                <td>{$team->position}</td>
                <td>{link href="FUTBOL EQUIPO {$liga->payload->id} {$team->team->id}" caption="{$team->team->name}"}</td>
                <td>{$team->playedGames}</td>
                <td>{$team->points}</td>
                <td>{$team->goalsFor}</td>
                <td>{$team->goalsAgainst}</td>
                <td>{$team->goalDifference}</td>
            </tr>
        {/foreach}
    	</table>
        {/if}
	{/foreach}
{/if}
{space5}
<!--<p>Posiciones en la jornada {$liga->payload->currentMatchday} de {$liga->payload->numberOfMatchdays}.</p>-->

{space15}
<table style="text-align:center;" width="100%">
	<tr>
		<td style="" colspan="1">
	      	{button href="FUTBOL JORNADA {$liga->payload->id} {$liga->payload->currentSeason->currentMatchday}" caption="Jornada Actual" color="green"}
	    </td>
	    <td style="" colspan="1">
	     	{button href="FUTBOL JORNADA {$liga->payload->id} TODAS" caption="Campeonato" color="green"}
	    </td>
	</tr>
</table>
{space10}