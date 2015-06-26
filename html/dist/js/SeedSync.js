/**
 * Created by ShaunBetts on 13/03/2015.
 */
console.log('Hello');

//@TODO Search box
//@TODO Action buttons
//@TODO Order By

(function(){
    var me;

    var seedSync = function(){
        me = this;
        this.hosts = false;
        this.activeHost = 'all';
        this.timePeriod = 'day';
        this.search = false;

        var hosts = this.getHosts();
        var config = this.getConfig();
        var navBar = Ractive.load('templates/nav.html');
        Promise.all([hosts,navBar]).then(function(values){

            var hosts = values[0];

            var FooComponent = values[1];

            console.log(config);

            var ractive = new FooComponent({
                el: 'nav',
                data: {
                    hosts: hosts,
                    config:me.config
                }
            });

            ractive.on( 'changeMode',function(event){
                var newMode = event.node.getAttribute('data-mode');
                me.setConfig('mode',newMode).then(function(){
                    ractive.set('config.mode',newMode);
                });
            });
        });
        this.getDownloads();
    };

    seedSync.prototype.getConfig = function()
    {
        return new Promise(function(resolve,reject){
            var ajax = $.get('./php/api.php?action=getConfig')
            var getConfig = Promise.resolve(ajax)
            getConfig.then(function(config){
                me.config = config;
                resolve(config);
            });
            getConfig.catch(function(){
                console.log('Could not load config');
            });
        });
    };

    seedSync.prototype.setConfig = function(property,value)
    {
        return new Promise(function(resolve,reject){
            var ajax = $.post('./php/api.php?action=setConfig',{propertyName:property,propertyValue:value});
            var setConfig = Promise.resolve(ajax)
            setConfig.then(function(result){
                resolve(result);
            });
            setConfig.catch(function(){
                console.log('Could save config');
            });
        });
    };

    seedSync.prototype.getHosts = function()
    {
        return new Promise(function(resolve,reject){
            var ajax = $.get('./php/api.php?action=getHosts')
            var getHosts = Promise.resolve(ajax)
            getHosts.then(function(hosts){
                me.hosts = hosts;
                resolve(hosts);
            });
            getHosts.catch(function(){
                console.log('Could not load hosts');
            });
        });
    };

    seedSync.prototype.getDownloads = function()
    {
        var downloads = this.queryDb();
        var table = Ractive.load('templates/table.html');
        Promise.all([downloads,table]).then(function(values){
            var hmm = values[0];
            var FooComponent = values[1];

            var ractive = new FooComponent({
                el: '#page-wrapper',
                data: {
                    downloads: hmm
                }
            });
        });
    }

    seedSync.prototype.queryDb = function()
    {
        return new Promise(function(resolve,reject){
            var ajax = $.post('./php/api.php?action=getDownloads',{host:me.activeHost,timePeriod:me.timePeriod,sort:'Priority,DateAdded',search:false})
            var getDownloads = Promise.resolve(ajax)
            getDownloads.then(function(downloads){
                resolve(downloads);
            });
            getDownloads.catch(function(){
                console.log('Could not get downloads');
            });
        });
    }

    seedSync.prototype.changeView = function(event)
    {
        var timePeriod = event.node.getAttribute('data-time');
        console.log(event,timePeriod);
        me.activeHost = event.context.id;
        me.timePeriod = timePeriod;
        me.getDownloads();
    }

    new seedSync();


}());

