------------------------------------------------------------------------------
--                   INSTRUCTION TO TRANSLATE THIS PLUGIN                   --
------------------------------------------------------------------------------

Get translations

    You can find a current translation file in our community
    driven translation project at this URL:
    http://premium.wpmudev.org/translate/projects/membership

.po or .mo

    WordPress needs the .mo file to translate the plugin.

    You only need the .po file when you want to make changes to the
    translations via POEdit or a similar tool. POEdit uses the .po file to
    create the required .mo file.

Correct filename

    Translation files must have the name "membership2-<locale>.mo"

    Example translations:
        membership2-fr_FR.mo  (french translation for France)
        membership2-fr_CH.mo  (french translation for Switzerland)
        membership2-de_CH.mo  (german translation for Switzerland)

Directory

    Save the .mo file with the correct filenmae to either of these two
    directories:

    /wp-content/languages/plugins/<mo-file>                <-- Best choice!
    /wp-content/plugins/membership/languages/<mo-file>     <-- Not recommented

    The second option is not recommented because your custom translation will
    be lost when you update the plugin.