


$(document).ready(function () {

    
  showOhiStatus();
  
  function showOhiStatus() {
    
    $.ajax({
        method: "GET",
        url: "/dashboard/ohi-status",
        timeout: 60000,
        data: {
            '_token' : '{{ csrf_token() }}'
        },
        success: function (response) {

            drawOhiStatus(response.ohis);
            
        }, 
        error: function (error) {
            console.log(error);
            console.log('loading ohi error');
        }
    });

  }
  

  //Draw chart
  function drawOhiStatus(ohis) {

      if( typeof (echarts) === 'undefined'){ return; }

      var theme = {
        color: [
            '#db2828', '#21ba45', '#1abb9c', '#9b59b6', 
            '#34495e', '#8abb6f', '#759c6a', '#bfd3b7'
        ],

        title: {
            itemGap: 8,
            textStyle: {
                fontWeight: 'normal',
                color: '#408829'
            }
        },

        dataRange: {
            color: ['#1f610a', '#97b58d']
        },

        toolbox: {
            color: ['#408829', '#408829', '#408829', '#408829']
        },

        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.5)',
            axisPointer: {
                type: 'line',
                lineStyle: {
                    color: '#408829',
                    type: 'dashed'
                },
                crossStyle: {
                    color: '#408829'
                },
                shadowStyle: {
                    color: 'rgba(200,200,200,0.3)'
                }
            }
        },

        textStyle: {
            fontFamily: 'Arial, Verdana, sans-serif'
          }
      };

    
    //echart Bar
      
      if ($('#follower_chart').length ){
          
          var $echartBar = echarts.init(document.getElementById('follower_chart'), theme);
          
          var xAxis = [],
              for_process = [],
              processed = [];

          for( var i = 0; i < ohis.length ; i ++) {
            xAxis[i] = ohis[ohis.length - i -1].completed_at;
            for_process[i] = ohis[ohis.length - i -1].for_process;
            processed[i] = ohis[ohis.length - i -1].processed;
          }

          $echartBar.setOption({
            // title: {
            //   text: 'Graph title',
            //   subtext: 'Graph Sub-text'
            // },
            tooltip: {
              trigger: 'axis'
            },
            legend: {
              data: ['Now', 'Sent']
            },
            toolbox: {
              show: false
            },
            calculable: false,
            xAxis: [{
              type: 'category',
              data: xAxis
            }],
            yAxis: [{
              type: 'value'
            }],
            series: [{
              name: 'Now',
              type: 'bar',
              data: for_process,
              markPoint: {
                data: [{
                  type: 'max',
                  name: 'Max'
                }, {
                  name: 'Now',
                  value: for_process[ohis.length - 1],
                  xAxis: ohis.length - 1,
                  yAxis: for_process[ohis.length - 1]
                }]
              },
              markLine: {
                data: [{
                  type: 'average',
                  name: 'Average'
                }]
              }
            }, {
              name: 'Sent',
              type: 'bar',
              data: processed,
              markPoint: {
                data: [{
                  type: 'max',
                  name: 'Max'
                }, {
                    name: 'Links processed',
                    value: processed[ohis.length - 1],
                    xAxis: ohis.length - 1,
                    yAxis: processed[ohis.length - 1]
                }]
              },
              markLine: {
                data: [{
                  type: 'average',
                  name: 'Average'
                }]
              }
            }]
          });

    }
  }
});