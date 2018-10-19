<center>
<h1>{$titulo}</h1>

{space10}
{if $equipo|lower == "todos"}
	<table style="text-align:center" width="100%">
	    <tr>
	        <!--<th><h2>crestUrl</th>-->
	        <th><h2>Nombre</th>
	        <!--<th><h2>code</th>-->
	        <th><h2>Nombre Corto</th>
	        <th><h2>Stadium</th>
	    </tr>
	    {foreach $equipos as $team}
	    <tr bgcolor="{cycle values="#f2f2f2,white"}">
	        <td><b>{link href="FUTBOL EQUIPO {$liga->payload->id} {$team->id}" caption="{$team->name}"}</b></td>
	        <td>{$team->shortName}</td>
	        <td>{$team->venue}</td>
	    </tr>
	    {/foreach}
	</table>
	{space15}
	<table style="text-align:center;" width="100%">
		<tr>
		    <td style="" colspan="1">
			    {button href="FUTBOL LIGA {$liga->payload->id}" caption="Ver Liga" color="green" size="small"}
			</td>
		</tr>
	</table>
{else}
	<table style="text-align:center" width="100%">
	    <tr>
	        <th colspan="2">{img src="{$imgTeam}" alt="TeamLogo" width="100px" height="130px"}</th>
	    </tr>
	    <!--<tr>
	        <td colspan="2"><h2><b>{$equipos->_payload->name}</b></h2></td>
	    </tr>-->
	    <tr>
	        <th><h2>Nombre Corto</h2></th>
	        <th><h2>Stadium</h2></th>
	    </tr>
	    <tr>
	        <td>{$equipos->_payload->shortName}</td>
	        <td>{$equipos->_payload->venue}</td>
	    </tr>
	</table>
	{space15}
	<h2>Jugadores del {$equipos->_payload->name}</h2>
	<table style="text-align:center" width="100%">
        <tr>
            <th><h2>Nombre</h2></th>
            <th><h2>Posici&oacute;n</h2></th>
            <!--<th><h2># Camiseta</h2></th>-->
            <th><h2>Fecha de Nacimiento</h2></th>
        </tr>
        {foreach $equipos->_payload->squad as $player}
        <tr>
            <td><b>{$player->name}</b></td>
            <td>{$player->position}</td>
            <!--<td>{$player->jerseyNumber}</td>-->
            <td>{$player->dateOfBirth|date_format:"%d/%m/%Y"}</td>
        </tr>
        {/foreach}
    </table>
    {space15}
	<h2>Partidos en casa del {$equipos->_payload->name}</h2>
	<table style="text-align:center" width="100%">
        <tr>
            <th><h2>Fecha</h2></th>
            <th><h2>Local</h2></th>
            <th><h2></h2></th>
            <th><h2>Visitante</h2></th>
            <th colspan="3"><h2>Resultado</h2></th>
        </tr>
        {foreach $juegosHome as $juego}
        <tr>
            <td>{$juego->utcDate|date_format:"%d/%m/%Y"}</td>
            <td><b>{$juego->homeTeam->name}</b></td>
            <td>-</td>
            <td>{$juego->awayTeam->name}</td>
            <td><b>{$juego->score->fullTime->homeTeam}</b></td>
            <td>:</td>
            <td>{$juego->score->fullTime->awayTeam}</td>
        </tr>
        {/foreach}
    </table>
    {space15}
	<h2>Partidos de visitante del {$equipos->_payload->name}</h2>
	<table style="text-align:center" width="100%">
        <tr>
            <th><h2>Fecha</h2></th>
            <th><h2>Local</h2></th>
            <th><h2></h2></th>
            <th><h2>Visitante</h2></th>
            <th colspan="3"><h2>Resultado</h2></th>
        </tr>
        {foreach $juegosAway as $juego}
        <tr>
            <td>{$juego->utcDate|date_format:"%d/%m/%Y"}</td>
            <td>{$juego->homeTeam->name}</td>
            <td>-</td>
            <td><b>{$juego->awayTeam->name}</b></td>
            <td>{$juego->score->fullTime->homeTeam}</td>
            <td>:</td>
            <td><b>{$juego->score->fullTime->awayTeam}</b></td>
        </tr>
        {/foreach}
    </table>
    {space15}
	<table style="text-align:center;" width="100%">
		<tr>
		    <td style="" colspan="1">
		     	{button href="FUTBOL EQUIPO {$liga->payload->id} TODOS" caption="Ver Equipos" color="green"}
		    </td>
		    <td style="" colspan="1">
			    {button href="FUTBOL LIGA {$liga->payload->id}" caption="Ver Liga" color="green"}
			</td>
		</tr>
	</table>
{/if}
{space10}